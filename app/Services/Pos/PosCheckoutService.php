<?php

namespace App\Services\Pos;

use App\Enums\ContactType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Enums\PosPaymentMethod;
use App\Enums\PosSaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\GiftCard;
use App\Models\Invoice;
use App\Models\LoyaltyBalance;
use App\Models\LoyaltyProgram;
use App\Models\Payment;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSalePayment;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Services\Accounting\AutoPostingService;
use App\Services\Inventory\StockLedgerService;
use App\Support\Inventory\MovementCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PosCheckoutService
{
    public function __construct(
        protected PosPricingService $pricing,
        protected PosCatalogService $catalog,
        protected StockLedgerService $ledger,
        protected PosWarehouseResolver $warehouses,
    ) {}

    /**
     * @param  list<array{product_id?: int|null, variant_id?: int|null, name?: string, sku?: string|null, barcode?: string|null, quantity: int, unit_price: float|int|string, discount_amount?: float|int|string, tax_rate?: float|int|string}>  $items
     * @param  list<array{method: string, amount: float|int|string, gift_card_code?: string|null, reference?: string|null}>  $payments
     */
    public function checkout(
        PosShift $shift,
        User $user,
        array $items,
        array $payments,
        ?int $contactId = null,
        float $cartDiscount = 0,
        ?string $couponCode = null,
        float $loyaltyPointsToRedeem = 0,
        ?string $notes = null,
        bool $openCashDrawer = true,
    ): PosSale {
        if (! $shift->isOpen()) {
            throw new InvalidArgumentException(__('Shift is closed.'));
        }

        if ($items === []) {
            throw new InvalidArgumentException(__('Cart is empty.'));
        }

        $coupon = null;
        if ($couponCode) {
            $coupon = Coupon::query()->where('code', $couponCode)->first();
            if (! $coupon || ! $coupon->isRedeemable()) {
                throw new InvalidArgumentException(__('Invalid or expired coupon.'));
            }
        }

        $defaultTax = $this->catalog->defaultTaxRate();
        $totals = $this->pricing->calculate($items, $cartDiscount, $coupon, $defaultTax);

        $loyaltyDiscount = 0.0;
        if ($loyaltyPointsToRedeem > 0) {
            if (! $contactId) {
                throw new InvalidArgumentException(__('Customer required for loyalty redemption.'));
            }
            $loyaltyDiscount = $this->loyaltyCurrencyValue($loyaltyPointsToRedeem);
            $totals['discount_total'] = round(min($totals['subtotal'], $totals['discount_total'] + $loyaltyDiscount), 2);
            $taxableBase = max(0, $totals['subtotal'] - $totals['discount_total']);
            $totals['tax'] = $totals['subtotal'] > 0
                ? round($totals['tax'] * ($taxableBase / max($totals['subtotal'], 0.0001)), 2)
                : 0.0;
            $totals['total'] = round($taxableBase + $totals['tax'], 2);
        }

        $paymentTotal = round(collect($payments)->sum(fn ($p) => (float) $p['amount']), 2);
        if (abs($paymentTotal - $totals['total']) > 0.01) {
            throw new InvalidArgumentException(__('Payment total must equal sale total.'));
        }

        $ledgerEnabled = (bool) config('inventory.ledger_enabled', false);

        if ($ledgerEnabled) {
            $this->warehouses->resolveFromShift($shift);
        }

        return DB::transaction(function () use (
            $shift,
            $user,
            $items,
            $payments,
            $contactId,
            $coupon,
            $totals,
            $loyaltyPointsToRedeem,
            $notes,
            $openCashDrawer,
            $defaultTax,
            $ledgerEnabled,
        ) {
            if (! $ledgerEnabled) {
                foreach ($items as $item) {
                    $this->assertAndDeductStock($item);
                }
            }

            if ($coupon) {
                $coupon->increment('usage_count');
            }

            $invoice = Invoice::query()->create([
                'reference_number' => $this->nextInvoiceReference(),
                'contact_id' => $contactId,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'status' => InvoiceStatus::Paid,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount_total'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
                'notes' => $notes,
                'source' => 'pos',
            ]);

            $opensDrawer = false;
            foreach ($payments as $payment) {
                $method = PosPaymentMethod::from($payment['method']);
                if ($method->opensCashDrawer()) {
                    $opensDrawer = true;
                }
            }

            $pointsEarned = 0.0;
            if ($contactId && $totals['total'] > 0) {
                $pointsEarned = $this->earnLoyaltyPoints($contactId, $totals['total']);
            }

            if ($loyaltyPointsToRedeem > 0 && $contactId) {
                $this->redeemLoyaltyPoints($contactId, $loyaltyPointsToRedeem);
            }

            $sale = PosSale::query()->create([
                'reference_number' => $this->nextSaleReference(),
                'pos_shift_id' => $shift->id,
                'pos_register_id' => $shift->pos_register_id,
                'user_id' => $user->id,
                'contact_id' => $contactId,
                'invoice_id' => $invoice->id,
                'coupon_id' => $coupon?->id,
                'status' => PosSaleStatus::Completed,
                'is_return' => false,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount_total'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
                'loyalty_points_earned' => $pointsEarned,
                'loyalty_points_redeemed' => $loyaltyPointsToRedeem,
                'cash_drawer_opened' => $openCashDrawer && $opensDrawer && $shift->register?->cash_drawer_enabled,
                'notes' => $notes,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($items as $index => $item) {
                $line = $totals['lines'][$index];
                $product = isset($item['product_id']) ? Product::query()->find($item['product_id']) : null;
                $variant = isset($item['variant_id']) ? Variant::query()->find($item['variant_id']) : null;

                PosSaleItem::query()->create([
                    'pos_sale_id' => $sale->id,
                    'product_id' => $product !== null ? $product->id : ($item['product_id'] ?? null),
                    'variant_id' => $variant !== null ? $variant->id : ($item['variant_id'] ?? null),
                    'name' => $item['name']
                        ?? ($variant !== null ? $variant->name : null)
                        ?? ($product !== null ? $product->name : null)
                        ?? __('Item'),
                    'sku' => $item['sku']
                        ?? ($variant !== null ? $variant->sku : null)
                        ?? ($product !== null ? $product->sku : null),
                    'barcode' => $item['barcode']
                        ?? ($variant !== null ? $variant->barcode : null)
                        ?? ($product !== null ? $product->barcode : null),
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => $line['discount_amount'],
                    'tax_rate' => $line['tax_rate'] ?: $defaultTax,
                    'tax_amount' => $line['tax_amount'],
                    'line_total' => $line['line_total'],
                ]);
            }

            if ($ledgerEnabled) {
                $this->postLedgerSaleStock($sale, $shift, $user);
            }

            foreach ($payments as $paymentData) {
                $method = PosPaymentMethod::from($paymentData['method']);
                $amount = round((float) $paymentData['amount'], 2);
                $giftCard = null;

                if ($method === PosPaymentMethod::GiftCard) {
                    $giftCard = $this->redeemGiftCard((string) ($paymentData['gift_card_code'] ?? ''), $amount);
                }

                $payment = Payment::query()->create([
                    'reference_number' => 'PAY-POS-'.strtoupper(Str::random(8)),
                    'contact_id' => $contactId,
                    'invoice_id' => $invoice->id,
                    'gift_card_id' => $giftCard?->id,
                    'payment_date' => now()->toDateString(),
                    'amount' => $amount,
                    'type' => PaymentType::Incoming,
                    'payment_method' => $method->value,
                    'notes' => $paymentData['reference'] ?? null,
                ]);

                PosSalePayment::query()->create([
                    'pos_sale_id' => $sale->id,
                    'payment_id' => $payment->id,
                    'gift_card_id' => $giftCard?->id,
                    'method' => $method,
                    'amount' => $amount,
                    'reference' => $paymentData['reference'] ?? $giftCard?->code,
                ]);
            }

            $sale = $sale->load(['items.product', 'payments', 'invoice', 'contact']);

            try {
                app(AutoPostingService::class)->postPosSale($sale, $user);
            } catch (\Throwable $e) {
                Log::warning('pos.accounting_post_failed', [
                    'sale_id' => $sale->id,
                    'message' => $e->getMessage(),
                ]);
            }

            return $sale;
        });
    }

    protected function postLedgerSaleStock(PosSale $sale, PosShift $shift, User $user): void
    {
        $warehouse = $this->warehouses->resolveFromShift($shift);
        $sale->loadMissing('items.variant');

        $lines = $sale->items
            ->sortBy(function (PosSaleItem $item) {
                $variant = $item->variant;
                $productId = $item->product_id ?? ($variant !== null ? $variant->product_id : 0);

                return sprintf('%020d-%020d', (int) $productId, (int) ($item->variant_id ?? 0));
            })
            ->values();

        foreach ($lines as $item) {
            $variant = $item->variant;
            $productId = $item->product_id ?? ($variant !== null ? $variant->product_id : null);

            if ($productId === null) {
                throw new InvalidArgumentException(__('Sale line is missing a product.'));
            }

            $qty = max(1, (int) $item->quantity);

            try {
                $this->ledger->post(MovementCommand::fromArray([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => (int) $productId,
                    'variant_id' => $item->variant_id,
                    'quantity' => -$qty,
                    'reserved_delta' => 0,
                    'movement_type' => StockMovementType::PosSale,
                    'idempotency_key' => sprintf('pos_sale:%d:line:%d', $sale->id, $item->id),
                    'occurred_at' => now(),
                    'reference_type' => PosSale::class,
                    'reference_id' => $sale->id,
                    'reference_line_id' => $item->id,
                    'created_by' => $user->id,
                    'allow_inactive' => false,
                    'notes' => $sale->reference_number,
                ]));
            } catch (InsufficientStockException $exception) {
                throw new InvalidArgumentException(
                    __('Insufficient stock for :name.', ['name' => $item->name]),
                    0,
                    $exception,
                );
            }
        }
    }

    /**
     * @param  array{product_id?: int|null, variant_id?: int|null, quantity: int}  $item
     */
    protected function assertAndDeductStock(array $item): void
    {
        $qty = max(1, (int) $item['quantity']);

        if (! empty($item['variant_id'])) {
            $variant = Variant::query()->lockForUpdate()->find($item['variant_id']);
            if (! $variant || ! $variant->is_active) {
                throw new InvalidArgumentException(__('Variant not available.'));
            }
            if ($variant->stock_quantity < $qty) {
                throw new InvalidArgumentException(__('Insufficient stock for :name.', ['name' => $variant->name]));
            }
            $variant->decrement('stock_quantity', $qty);

            return;
        }

        if (! empty($item['product_id'])) {
            $product = Product::query()->lockForUpdate()->find($item['product_id']);
            if (! $product || ! $product->is_active) {
                throw new InvalidArgumentException(__('Product not available.'));
            }
            if ($product->stock_quantity < $qty) {
                throw new InvalidArgumentException(__('Insufficient stock for :name.', ['name' => $product->name]));
            }
            $product->decrement('stock_quantity', $qty);
        }
    }

    protected function redeemGiftCard(string $code, float $amount): GiftCard
    {
        $giftCard = GiftCard::query()->where('code', $code)->lockForUpdate()->first();

        if (! $giftCard || ! $giftCard->is_active) {
            throw new InvalidArgumentException(__('Gift card not found.'));
        }

        if ($giftCard->expires_at->isPast()) {
            throw new InvalidArgumentException(__('Gift card has expired.'));
        }

        if ((float) $giftCard->current_balance < $amount) {
            throw new InvalidArgumentException(__('Insufficient gift card balance.'));
        }

        $giftCard->decrement('current_balance', $amount);

        return $giftCard->fresh();
    }

    protected function earnLoyaltyPoints(int $contactId, float $amount): float
    {
        $program = LoyaltyProgram::query()->where('is_active', true)->orderBy('id')->first();

        if (! $program) {
            return 0.0;
        }

        $points = round($amount * (float) $program->points_per_currency, 2);

        if ($points <= 0) {
            return 0.0;
        }

        $balance = LoyaltyBalance::query()->firstOrCreate(
            [
                'contact_id' => $contactId,
                'loyalty_program_id' => $program->id,
            ],
            ['points' => 0],
        );

        $balance->increment('points', $points);

        return $points;
    }

    protected function redeemLoyaltyPoints(int $contactId, float $points): void
    {
        $program = LoyaltyProgram::query()->where('is_active', true)->orderBy('id')->first();

        if (! $program) {
            throw new InvalidArgumentException(__('No active loyalty program.'));
        }

        $balance = LoyaltyBalance::query()
            ->where('contact_id', $contactId)
            ->where('loyalty_program_id', $program->id)
            ->lockForUpdate()
            ->first();

        if (! $balance || (float) $balance->points < $points) {
            throw new InvalidArgumentException(__('Insufficient loyalty points.'));
        }

        $balance->decrement('points', $points);
    }

    protected function loyaltyCurrencyValue(float $points): float
    {
        $program = LoyaltyProgram::query()->where('is_active', true)->orderBy('id')->first();

        if (! $program || (float) $program->points_per_currency <= 0) {
            return 0.0;
        }

        return round($points / (float) $program->points_per_currency, 2);
    }

    protected function nextSaleReference(): string
    {
        return 'POS-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    protected function nextInvoiceReference(): string
    {
        return 'INV-POS-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    public function quickCreateCustomer(User $user, string $name, ?string $phone = null, ?string $email = null): Contact
    {
        return Contact::query()->create([
            'name' => $name,
            'type' => ContactType::Customer,
            'phone' => $phone,
            'email' => $email,
            'opening_balance' => 0,
        ]);
    }
}
