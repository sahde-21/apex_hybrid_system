<?php

namespace App\Services\Pos;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Enums\PosPaymentMethod;
use App\Enums\PosSaleStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSalePayment;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PosRefundService
{
    /**
     * @param  list<array{pos_sale_item_id: int, quantity: int}>|null  $items null = full refund
     * @param  list<array{method: string, amount: float|int|string, reference?: string|null}>  $refundPayments
     */
    public function refund(
        PosSale $original,
        User $user,
        ?array $items = null,
        array $refundPayments = [],
        ?string $notes = null,
    ): PosSale {
        if ($original->is_return) {
            throw new InvalidArgumentException(__('Cannot refund a return sale.'));
        }

        if (in_array($original->status, [PosSaleStatus::Refunded, PosSaleStatus::Voided], true)) {
            throw new InvalidArgumentException(__('Sale cannot be refunded.'));
        }

        /** @var PosShift $shift */
        $shift = $original->shift;
        if (! $shift->isOpen() || $shift->user_id !== $user->id) {
            // Allow refund on any open shift for same register by authorized users.
            $openShift = PosShift::query()
                ->where('pos_register_id', $original->pos_register_id)
                ->where('status', 'open')
                ->where('user_id', $user->id)
                ->first();

            if (! $openShift) {
                throw new InvalidArgumentException(__('Open a shift on this register to process refunds.'));
            }
            $shift = $openShift;
        }

        $original->loadMissing('items');

        $refundLines = [];
        if ($items === null) {
            foreach ($original->items as $item) {
                $refundLines[] = [
                    'item' => $item,
                    'quantity' => (int) $item->quantity,
                ];
            }
        } else {
            foreach ($items as $payload) {
                $item = $original->items->firstWhere('id', $payload['pos_sale_item_id']);
                if (! $item) {
                    throw new InvalidArgumentException(__('Sale item not found.'));
                }
                $qty = max(1, (int) $payload['quantity']);
                if ($qty > $item->quantity) {
                    throw new InvalidArgumentException(__('Refund quantity exceeds sold quantity.'));
                }
                $refundLines[] = ['item' => $item, 'quantity' => $qty];
            }
        }

        if ($refundLines === []) {
            throw new InvalidArgumentException(__('Nothing to refund.'));
        }

        $subtotal = 0.0;
        $tax = 0.0;
        $discount = 0.0;

        foreach ($refundLines as $line) {
            /** @var PosSaleItem $item */
            $item = $line['item'];
            $ratio = $line['quantity'] / max(1, (int) $item->quantity);
            $subtotal += ((float) $item->unit_price * $line['quantity']);
            $tax += (float) $item->tax_amount * $ratio;
            $discount += (float) $item->discount_amount * $ratio;
        }

        $total = round(max(0, $subtotal - $discount + $tax), 2);

        if ($refundPayments === []) {
            $refundPayments = [[
                'method' => PosPaymentMethod::Cash->value,
                'amount' => $total,
            ]];
        }

        $paymentTotal = round(collect($refundPayments)->sum(fn ($p) => (float) $p['amount']), 2);
        if (abs($paymentTotal - $total) > 0.01) {
            throw new InvalidArgumentException(__('Refund payment total must equal refund total.'));
        }

        return DB::transaction(function () use ($original, $user, $shift, $refundLines, $refundPayments, $subtotal, $tax, $discount, $total, $notes) {
            foreach ($refundLines as $line) {
                $this->restock($line['item'], $line['quantity']);
            }

            $invoice = Invoice::query()->create([
                'reference_number' => 'INV-REF-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'contact_id' => $original->contact_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'status' => InvoiceStatus::Paid,
                'subtotal_amount' => round($subtotal, 2) * -1,
                'discount_amount' => round($discount, 2),
                'tax_amount' => round($tax, 2) * -1,
                'total_amount' => $total * -1,
                'notes' => $notes ?? __('Refund for :ref', ['ref' => $original->reference_number]),
                'source' => 'pos',
            ]);

            $sale = PosSale::query()->create([
                'reference_number' => 'POS-REF-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'pos_shift_id' => $shift->id,
                'pos_register_id' => $shift->pos_register_id,
                'user_id' => $user->id,
                'contact_id' => $original->contact_id,
                'invoice_id' => $invoice->id,
                'original_sale_id' => $original->id,
                'status' => PosSaleStatus::Completed,
                'is_return' => true,
                'subtotal_amount' => round($subtotal, 2),
                'discount_amount' => round($discount, 2),
                'tax_amount' => round($tax, 2),
                'total_amount' => $total,
                'cash_drawer_opened' => collect($refundPayments)->contains(fn ($p) => ($p['method'] ?? '') === 'cash'),
                'notes' => $notes,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($refundLines as $line) {
                /** @var PosSaleItem $item */
                $item = $line['item'];
                $ratio = $line['quantity'] / max(1, (int) $item->quantity);

                PosSaleItem::query()->create([
                    'pos_sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'barcode' => $item->barcode,
                    'quantity' => $line['quantity'],
                    'unit_price' => $item->unit_price,
                    'discount_amount' => round((float) $item->discount_amount * $ratio, 2),
                    'tax_rate' => $item->tax_rate,
                    'tax_amount' => round((float) $item->tax_amount * $ratio, 2),
                    'line_total' => round((float) $item->line_total * $ratio, 2),
                ]);
            }

            foreach ($refundPayments as $paymentData) {
                $method = PosPaymentMethod::from($paymentData['method']);
                $amount = round((float) $paymentData['amount'], 2);

                $payment = Payment::query()->create([
                    'reference_number' => 'PAY-REF-'.strtoupper(Str::random(8)),
                    'contact_id' => $original->contact_id,
                    'invoice_id' => $invoice->id,
                    'payment_date' => now()->toDateString(),
                    'amount' => $amount,
                    'type' => PaymentType::Outgoing,
                    'payment_method' => $method->value,
                    'notes' => $paymentData['reference'] ?? null,
                ]);

                PosSalePayment::query()->create([
                    'pos_sale_id' => $sale->id,
                    'payment_id' => $payment->id,
                    'method' => $method,
                    'amount' => $amount,
                    'reference' => $paymentData['reference'] ?? null,
                ]);
            }

            $refundedTotal = (float) $original->refunds()->sum('total_amount') + $total;
            $original->update([
                'status' => $refundedTotal + 0.01 >= (float) $original->total_amount
                    ? PosSaleStatus::Refunded
                    : PosSaleStatus::PartiallyRefunded,
                'updated_by' => $user->id,
            ]);

            return $sale->load(['items', 'payments', 'invoice']);
        });
    }

    protected function restock(PosSaleItem $item, int $quantity): void
    {
        if ($item->variant_id) {
            Variant::query()->whereKey($item->variant_id)->increment('stock_quantity', $quantity);

            return;
        }

        if ($item->product_id) {
            Product::query()->whereKey($item->product_id)->increment('stock_quantity', $quantity);
        }
    }
}
