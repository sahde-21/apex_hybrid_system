<?php

namespace App\Services\Sales;

use App\Enums\InvoiceStatus;
use App\Enums\SaleOrderStatus;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\SaleOrder;
use App\Models\SaleOrderLine;
use App\Models\User;
use App\Support\Sales\DocumentLineCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleOrderWorkflowService
{
    public function __construct(
        protected SalesDocumentEventLogger $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function create(User $user, array $data, array $lines = []): SaleOrder
    {
        abort_unless($user->can('sale-orders.create'), 403);

        return DB::transaction(function () use ($user, $data, $lines): SaleOrder {
            $totals = DocumentLineCalculator::summarize($lines);

            $order = SaleOrder::query()->create([
                'reference_number' => $data['reference_number'],
                'contact_id' => $data['contact_id'] ?? null,
                'quotation_id' => $data['quotation_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'salesperson_id' => $data['salesperson_id'] ?? $user->id,
                'order_date' => $data['order_date'],
                'delivery_date' => $data['delivery_date'] ?? null,
                'status' => SaleOrderStatus::Draft,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? 0),
                'currency_code' => $data['currency_code'] ?? config('accounting.base_currency', 'IQD'),
                'notes' => $data['notes'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            $this->syncLines($order, $totals['lines']);
            $this->events->log($order, 'created', $user, null, SaleOrderStatus::Draft->value);

            return $order->fresh(['lines', 'contact']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function update(SaleOrder $order, User $user, array $data, array $lines = []): SaleOrder
    {
        abort_unless($user->can('sale-orders.update'), 403);

        if (! $order->status->isEditable()) {
            throw ValidationException::withMessages([
                'sale_order' => [__('scf.sales_workflow.document_not_editable')],
            ]);
        }

        return DB::transaction(function () use ($order, $user, $data, $lines): SaleOrder {
            $totals = DocumentLineCalculator::summarize($lines);

            $order->update([
                'reference_number' => $data['reference_number'] ?? $order->reference_number,
                'contact_id' => $data['contact_id'] ?? $order->contact_id,
                'warehouse_id' => $data['warehouse_id'] ?? $order->warehouse_id,
                'branch_id' => $data['branch_id'] ?? $order->branch_id,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'delivery_date' => $data['delivery_date'] ?? $order->delivery_date,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? $order->total_amount),
                'currency_code' => $data['currency_code'] ?? $order->currency_code,
                'notes' => $data['notes'] ?? $order->notes,
                'billing_address' => $data['billing_address'] ?? $order->billing_address,
                'shipping_address' => $data['shipping_address'] ?? $order->shipping_address,
                'terms' => $data['terms'] ?? $order->terms,
            ]);

            $this->syncLines($order, $totals['lines']);
            $this->events->log($order, 'updated', $user);

            return $order->fresh(['lines', 'contact']);
        });
    }

    public function submit(SaleOrder $order, User $user): SaleOrder
    {
        abort_unless($user->can('sale-orders.submit') || $user->can('sale-orders.update'), 403);

        return $this->transition($order, $user, SaleOrderStatus::PendingApproval, 'submitted');
    }

    public function approve(SaleOrder $order, User $user, ?string $reason = null): SaleOrder
    {
        abort_unless($user->can('sale-orders.approve'), 403);

        return $this->transition($order, $user, SaleOrderStatus::Approved, 'approved', $reason);
    }

    public function rejectToDraft(SaleOrder $order, User $user, ?string $reason = null): SaleOrder
    {
        abort_unless($user->can('sale-orders.approve'), 403);

        return $this->transition($order, $user, SaleOrderStatus::Draft, 'rejected', $reason);
    }

    public function confirm(SaleOrder $order, User $user): SaleOrder
    {
        abort_unless($user->can('sale-orders.confirm') || $user->can('sale-orders.approve'), 403);

        if ($order->status === SaleOrderStatus::Draft) {
            $order = $this->transition($order, $user, SaleOrderStatus::PendingApproval, 'submitted');
        }

        if ($order->status === SaleOrderStatus::PendingApproval) {
            abort_unless($user->can('sale-orders.approve'), 403);
            $order = $this->transition($order, $user, SaleOrderStatus::Approved, 'approved');
        }

        return $this->transition($order, $user, SaleOrderStatus::Confirmed, 'confirmed');
    }

    public function cancel(SaleOrder $order, User $user, ?string $reason = null): SaleOrder
    {
        abort_unless($user->can('sale-orders.update'), 403);

        return $this->transition($order, $user, SaleOrderStatus::Cancelled, 'cancelled', $reason);
    }

    public function duplicate(SaleOrder $order, User $user): SaleOrder
    {
        abort_unless($user->can('sale-orders.create'), 403);
        $order->load('lines');

        return $this->create($user, [
            'reference_number' => $order->reference_number.'-COPY-'.now()->format('His'),
            'contact_id' => $order->contact_id,
            'warehouse_id' => $order->warehouse_id,
            'branch_id' => $order->branch_id,
            'order_date' => now()->toDateString(),
            'delivery_date' => $order->delivery_date?->toDateString(),
            'currency_code' => $order->currency_code,
            'notes' => $order->notes,
            'billing_address' => $order->billing_address,
            'shipping_address' => $order->shipping_address,
            'terms' => $order->terms,
            'total_amount' => $order->total_amount,
        ], array_values($order->lines->map(fn (SaleOrderLine $line) => [
            'product_id' => $line->product_id,
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit_price' => $line->unit_price,
            'discount_amount' => $line->discount_amount,
            'tax_amount' => $line->tax_amount,
        ])->all()));
    }

    /**
     * @param  list<array{sale_order_line_id: int, quantity: float|int|string}>|null  $lineQuantities
     */
    public function createInvoice(SaleOrder $order, User $user, ?array $lineQuantities = null): Invoice
    {
        abort_unless($user->can('sale-orders.invoice') || $user->can('invoices.create'), 403);

        return DB::transaction(function () use ($order, $user, $lineQuantities): Invoice {
            $locked = SaleOrder::query()->whereKey($order->id)->lockForUpdate()->with('lines')->firstOrFail();

            if (! $locked->status->canInvoice()) {
                throw ValidationException::withMessages([
                    'sale_order' => [__('scf.sales_workflow.order_not_invoiceable')],
                ]);
            }

            if (! $locked->contact_id) {
                throw ValidationException::withMessages([
                    'contact_id' => [__('scf.sales_workflow.customer_required')],
                ]);
            }

            $invoiceLines = [];
            foreach ($locked->lines as $line) {
                $qty = $lineQuantities === null
                    ? $line->quantityRemainingToInvoice()
                    : (float) (collect($lineQuantities)->firstWhere('sale_order_line_id', $line->id)['quantity'] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                if ($qty > $line->quantityRemainingToInvoice() + 0.0001) {
                    throw ValidationException::withMessages([
                        'quantity' => [__('scf.sales_workflow.over_invoicing')],
                    ]);
                }

                $ratio = (float) $line->quantity > 0 ? $qty / (float) $line->quantity : 0;
                $discount = round((float) $line->discount_amount * $ratio, 2);
                $tax = round((float) $line->tax_amount * $ratio, 2);
                $lineTotal = round(($qty * (float) $line->unit_price) - $discount + $tax, 2);

                $invoiceLines[] = [
                    'product_id' => $line->product_id,
                    'sale_order_line_id' => $line->id,
                    'description' => $line->description,
                    'quantity' => $qty,
                    'unit_price' => $line->unit_price,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'line_total' => $lineTotal,
                ];
            }

            if ($invoiceLines === []) {
                throw ValidationException::withMessages([
                    'lines' => [__('scf.sales_workflow.nothing_to_invoice')],
                ]);
            }

            $totals = DocumentLineCalculator::summarize($invoiceLines);

            $invoice = Invoice::query()->create([
                'reference_number' => 'INV-'.$locked->reference_number.'-'.now()->format('ymdHis').'-'.Str::upper(Str::random(4)),
                'contact_id' => $locked->contact_id,
                'sale_order_id' => $locked->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'status' => InvoiceStatus::Draft,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
                'paid_amount' => 0,
                'currency_code' => $locked->currency_code,
                'source' => 'sale_order',
                'notes' => $locked->notes,
            ]);

            foreach ($totals['lines'] as $line) {
                InvoiceLine::query()->create([
                    ...$line,
                    'invoice_id' => $invoice->id,
                ]);

                if (! empty($line['sale_order_line_id'])) {
                    $soLine = SaleOrderLine::query()->lockForUpdate()->whereKey($line['sale_order_line_id'])->first();
                    if ($soLine instanceof SaleOrderLine) {
                        $soLine->update([
                            'quantity_invoiced' => (float) $soLine->quantity_invoiced + (float) $line['quantity'],
                        ]);
                    }
                }
            }

            $this->refreshInvoiceStatus($locked);
            $this->events->log($locked, 'invoiced', $user, $locked->status->value, $locked->fresh()->status->value, null, (float) $invoice->total_amount, $invoice);
            $this->events->log($invoice, 'created_from_sale_order', $user, null, InvoiceStatus::Draft->value, null, (float) $invoice->total_amount, $locked);

            return $invoice->fresh(['lines', 'contact', 'saleOrder']);
        });
    }

    public function refreshInvoiceStatus(SaleOrder $order): SaleOrder
    {
        $order->load('lines');
        $totalQty = $order->lines->sum(fn (SaleOrderLine $l) => (float) $l->quantity);
        $invoicedQty = $order->lines->sum(fn (SaleOrderLine $l) => (float) $l->quantity_invoiced);

        if ($totalQty <= 0) {
            return $order;
        }

        $status = $order->status;
        if ($invoicedQty <= 0) {
            return $order;
        }

        if ($invoicedQty + 0.0001 >= $totalQty) {
            $status = SaleOrderStatus::Invoiced;
        } else {
            $status = SaleOrderStatus::PartiallyInvoiced;
        }

        if ($order->status !== $status && $order->status->canTransitionTo($status)) {
            $order->update(['status' => $status]);
        } elseif ($order->status !== $status) {
            // Force status when invoicing from confirmed/fulfilled paths
            $order->update(['status' => $status]);
        }

        return $order->fresh();
    }

    protected function transition(
        SaleOrder $order,
        User $user,
        SaleOrderStatus $to,
        string $event,
        ?string $reason = null,
    ): SaleOrder {
        return DB::transaction(function () use ($order, $user, $to, $event, $reason): SaleOrder {
            $locked = SaleOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.sales_workflow.invalid_transition', [
                        'from' => $locked->status->label(),
                        'to' => $to->label(),
                    ])],
                ]);
            }

            $from = $locked->status->value;
            $locked->update(['status' => $to]);
            $this->events->log($locked, $event, $user, $from, $to->value, $reason, (float) $locked->total_amount);

            return $locked->fresh(['lines', 'contact', 'quotation']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncLines(SaleOrder $order, array $lines): void
    {
        $order->lines()->delete();

        foreach ($lines as $line) {
            if (trim((string) ($line['description'] ?? '')) === '' && empty($line['product_id'])) {
                continue;
            }

            $order->lines()->create([
                ...$line,
                'quantity_invoiced' => 0,
                'quantity_fulfilled' => 0,
            ]);
        }
    }
}
