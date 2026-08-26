<?php

namespace App\Services\Purchasing;

use App\Enums\BillStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\Bill;
use App\Models\BillLine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Services\Sales\SalesDocumentEventLogger;
use App\Support\Sales\DocumentLineCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseOrderWorkflowService
{
    public function __construct(
        protected SalesDocumentEventLogger $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function create(User $user, array $data, array $lines = []): PurchaseOrder
    {
        abort_unless($user->can('purchase-orders.create'), 403);

        return DB::transaction(function () use ($user, $data, $lines) {
            $totals = DocumentLineCalculator::summarize($lines);

            $order = PurchaseOrder::query()->create([
                'reference_number' => $data['reference_number'],
                'contact_id' => $data['contact_id'] ?? null,
                'rfq_id' => $data['rfq_id'] ?? null,
                'purchase_request_id' => $data['purchase_request_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'buyer_id' => $data['buyer_id'] ?? $user->id,
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status' => PurchaseOrderStatus::Draft,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? 0),
                'currency_code' => $data['currency_code'] ?? config('accounting.base_currency', 'IQD'),
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            $this->syncLines($order, $totals['lines']);
            $this->events->log($order, 'created', $user, null, PurchaseOrderStatus::Draft->value);

            return $order->fresh(['lines', 'contact']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function update(PurchaseOrder $order, User $user, array $data, array $lines = []): PurchaseOrder
    {
        abort_unless($user->can('purchase-orders.update'), 403);

        if (! $order->status->isEditable()) {
            throw ValidationException::withMessages([
                'purchase_order' => [__('scf.purchase_workflow.document_not_editable')],
            ]);
        }

        return DB::transaction(function () use ($order, $user, $data, $lines) {
            $totals = DocumentLineCalculator::summarize($lines);

            $order->update([
                'reference_number' => $data['reference_number'] ?? $order->reference_number,
                'contact_id' => $data['contact_id'] ?? $order->contact_id,
                'warehouse_id' => $data['warehouse_id'] ?? $order->warehouse_id,
                'branch_id' => $data['branch_id'] ?? $order->branch_id,
                'buyer_id' => $data['buyer_id'] ?? $order->buyer_id,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'expected_date' => $data['expected_date'] ?? $order->expected_date,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? $order->total_amount),
                'currency_code' => $data['currency_code'] ?? $order->currency_code,
                'notes' => $data['notes'] ?? $order->notes,
                'terms' => $data['terms'] ?? $order->terms,
            ]);

            $this->syncLines($order, $totals['lines']);
            $this->events->log($order, 'updated', $user);

            return $order->fresh(['lines', 'contact']);
        });
    }

    public function submit(PurchaseOrder $order, User $user): PurchaseOrder
    {
        abort_unless($user->can('purchase-orders.submit') || $user->can('purchase-orders.update'), 403);

        return $this->transition($order, $user, PurchaseOrderStatus::PendingApproval, 'submitted');
    }

    public function approve(PurchaseOrder $order, User $user, ?string $reason = null): PurchaseOrder
    {
        abort_unless($user->can('purchase-orders.approve'), 403);

        return $this->transition($order, $user, PurchaseOrderStatus::Approved, 'approved', $reason);
    }

    public function rejectToDraft(PurchaseOrder $order, User $user, ?string $reason = null): PurchaseOrder
    {
        abort_unless($user->can('purchase-orders.approve'), 403);

        return $this->transition($order, $user, PurchaseOrderStatus::Draft, 'rejected', $reason);
    }

    public function confirm(PurchaseOrder $order, User $user): PurchaseOrder
    {
        abort_unless($user->can('purchase-orders.confirm') || $user->can('purchase-orders.approve'), 403);

        if ($order->status === PurchaseOrderStatus::Draft) {
            $order = $this->transition($order, $user, PurchaseOrderStatus::PendingApproval, 'submitted');
        }

        if ($order->status === PurchaseOrderStatus::PendingApproval) {
            abort_unless($user->can('purchase-orders.approve'), 403);
            $order = $this->transition($order, $user, PurchaseOrderStatus::Approved, 'approved');
        }

        return $this->transition($order, $user, PurchaseOrderStatus::Confirmed, 'confirmed');
    }

    public function cancel(PurchaseOrder $order, User $user, ?string $reason = null): PurchaseOrder
    {
        abort_unless($user->can('purchase-orders.update'), 403);

        return $this->transition($order, $user, PurchaseOrderStatus::Cancelled, 'cancelled', $reason);
    }

    public function duplicate(PurchaseOrder $order, User $user): PurchaseOrder
    {
        abort_unless($user->can('purchase-orders.create'), 403);
        $order->load('lines');

        return $this->create($user, [
            'reference_number' => $order->reference_number.'-COPY-'.now()->format('His'),
            'contact_id' => $order->contact_id,
            'warehouse_id' => $order->warehouse_id,
            'branch_id' => $order->branch_id,
            'buyer_id' => $user->id,
            'order_date' => now()->toDateString(),
            'expected_date' => $order->expected_date?->toDateString(),
            'currency_code' => $order->currency_code,
            'notes' => $order->notes,
            'terms' => $order->terms,
            'total_amount' => $order->total_amount,
        ], $order->lines->map(fn (PurchaseOrderLine $line) => [
            'product_id' => $line->product_id,
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit_price' => $line->unit_price,
            'discount_amount' => $line->discount_amount,
            'tax_amount' => $line->tax_amount,
        ])->all());
    }

    /**
     * @param  list<array{purchase_order_line_id: int, quantity: float|int|string}>|null  $lineQuantities
     */
    public function createBill(PurchaseOrder $order, User $user, ?array $lineQuantities = null): Bill
    {
        abort_unless($user->can('purchase-orders.bill') || $user->can('bills.create'), 403);

        return DB::transaction(function () use ($order, $user, $lineQuantities) {
            $locked = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->with('lines')->firstOrFail();

            if (! $locked->status->canBill()) {
                throw ValidationException::withMessages([
                    'purchase_order' => [__('scf.purchase_workflow.order_not_billable')],
                ]);
            }

            if (! $locked->contact_id) {
                throw ValidationException::withMessages([
                    'contact_id' => [__('scf.purchase_workflow.vendor_required')],
                ]);
            }

            $billLines = [];
            foreach ($locked->lines as $line) {
                $qty = $lineQuantities === null
                    ? $line->quantityRemainingToBill()
                    : (float) (collect($lineQuantities)->firstWhere('purchase_order_line_id', $line->id)['quantity'] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                if ($qty > $line->quantityRemainingToBill() + 0.0001) {
                    throw ValidationException::withMessages([
                        'quantity' => [__('scf.purchase_workflow.over_billing')],
                    ]);
                }

                $ratio = (float) $line->quantity > 0 ? $qty / (float) $line->quantity : 0;
                $discount = round((float) $line->discount_amount * $ratio, 2);
                $tax = round((float) $line->tax_amount * $ratio, 2);
                $lineTotal = round(($qty * (float) $line->unit_price) - $discount + $tax, 2);

                $billLines[] = [
                    'product_id' => $line->product_id,
                    'purchase_order_line_id' => $line->id,
                    'description' => $line->description,
                    'quantity' => $qty,
                    'unit_price' => $line->unit_price,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'line_total' => $lineTotal,
                ];
            }

            if ($billLines === []) {
                throw ValidationException::withMessages([
                    'lines' => [__('scf.purchase_workflow.nothing_to_bill')],
                ]);
            }

            $totals = DocumentLineCalculator::summarize($billLines);

            $bill = Bill::query()->create([
                'reference_number' => 'BILL-'.$locked->reference_number.'-'.now()->format('ymdHis').'-'.Str::upper(Str::random(4)),
                'contact_id' => $locked->contact_id,
                'purchase_order_id' => $locked->id,
                'bill_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'status' => BillStatus::Draft,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
                'paid_amount' => 0,
                'currency_code' => $locked->currency_code,
                'notes' => $locked->notes,
            ]);

            foreach ($totals['lines'] as $line) {
                BillLine::query()->create([
                    ...$line,
                    'bill_id' => $bill->id,
                ]);

                if (! empty($line['purchase_order_line_id'])) {
                    $poLine = PurchaseOrderLine::query()->lockForUpdate()->find($line['purchase_order_line_id']);
                    if ($poLine) {
                        $poLine->update([
                            'quantity_billed' => (float) $poLine->quantity_billed + (float) $line['quantity'],
                        ]);
                    }
                }
            }

            $this->refreshBillStatus($locked);
            $this->events->log($locked, 'billed', $user, $locked->status->value, $locked->fresh()->status->value, null, (float) $bill->total_amount, $bill);
            $this->events->log($bill, 'created_from_purchase_order', $user, null, BillStatus::Draft->value, null, (float) $bill->total_amount, $locked);

            return $bill->fresh(['lines', 'contact', 'purchaseOrder']);
        });
    }

    public function refreshBillStatus(PurchaseOrder $order): PurchaseOrder
    {
        $order->load('lines');
        $totalQty = $order->lines->sum(fn (PurchaseOrderLine $l) => (float) $l->quantity);
        $billedQty = $order->lines->sum(fn (PurchaseOrderLine $l) => (float) $l->quantity_billed);

        if ($totalQty <= 0 || $billedQty <= 0) {
            return $order;
        }

        $status = $order->status;

        if ($billedQty + 0.0001 >= $totalQty) {
            $status = PurchaseOrderStatus::FullyBilled;
        } else {
            $status = PurchaseOrderStatus::PartiallyBilled;
        }

        if ($order->status !== $status) {
            $order->update(['status' => $status]);
        }

        return $order->fresh();
    }

    protected function transition(
        PurchaseOrder $order,
        User $user,
        PurchaseOrderStatus $to,
        string $event,
        ?string $reason = null,
    ): PurchaseOrder {
        return DB::transaction(function () use ($order, $user, $to, $event, $reason) {
            $locked = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.purchase_workflow.invalid_transition', [
                        'from' => $locked->status->label(),
                        'to' => $to->label(),
                    ])],
                ]);
            }

            $from = $locked->status->value;
            $locked->update(['status' => $to]);
            $this->events->log($locked, $event, $user, $from, $to->value, $reason, (float) $locked->total_amount);

            return $locked->fresh(['lines', 'contact', 'rfq']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncLines(PurchaseOrder $order, array $lines): void
    {
        $order->lines()->delete();

        foreach ($lines as $line) {
            if (trim((string) ($line['description'] ?? '')) === '' && empty($line['product_id'])) {
                continue;
            }

            $order->lines()->create([
                'product_id' => $line['product_id'] ?? null,
                'rfq_line_id' => $line['rfq_line_id'] ?? null,
                'line_number' => $line['line_number'],
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount_amount' => $line['discount_amount'],
                'tax_amount' => $line['tax_amount'],
                'line_total' => $line['line_total'],
                'quantity_billed' => 0,
                'quantity_received' => 0,
                'quantity_returned' => 0,
            ]);
        }
    }
}
