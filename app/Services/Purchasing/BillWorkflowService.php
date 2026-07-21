<?php

namespace App\Services\Purchasing;

use App\Enums\BillStatus;
use App\Models\AccountingPosting;
use App\Models\Bill;
use App\Models\User;
use App\Services\Accounting\AutoPostingService;
use App\Services\Accounting\JournalEngineService;
use App\Services\Sales\SalesDocumentEventLogger;
use App\Support\Sales\DocumentLineCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillWorkflowService
{
    public function __construct(
        protected SalesDocumentEventLogger $events,
        protected AutoPostingService $posting,
        protected JournalEngineService $journals,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function create(User $user, array $data, array $lines = []): Bill
    {
        abort_unless($user->can('bills.create'), 403);

        return DB::transaction(function () use ($user, $data, $lines) {
            $totals = DocumentLineCalculator::summarize($lines);

            $bill = Bill::query()->create([
                'reference_number' => $data['reference_number'],
                'contact_id' => $data['contact_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'bill_date' => $data['bill_date'],
                'due_date' => $data['due_date'] ?? null,
                'status' => BillStatus::Draft,
                'subtotal_amount' => $totals['subtotal'] > 0 ? $totals['subtotal'] : ($data['subtotal_amount'] ?? 0),
                'discount_amount' => $totals['discount'] > 0 ? $totals['discount'] : ($data['discount_amount'] ?? 0),
                'tax_amount' => $totals['tax'] > 0 ? $totals['tax'] : ($data['tax_amount'] ?? 0),
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? 0),
                'paid_amount' => 0,
                'currency_code' => $data['currency_code'] ?? config('accounting.base_currency', 'IQD'),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($totals['lines'] as $line) {
                if (trim((string) ($line['description'] ?? '')) === '' && empty($line['product_id'])) {
                    continue;
                }
                $bill->lines()->create([
                    'product_id' => $line['product_id'] ?? null,
                    'purchase_order_line_id' => $line['purchase_order_line_id'] ?? null,
                    'line_number' => $line['line_number'],
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => $line['discount_amount'],
                    'tax_amount' => $line['tax_amount'],
                    'line_total' => $line['line_total'],
                ]);
            }

            $this->events->log($bill, 'created', $user, null, BillStatus::Draft->value);

            return $bill->fresh(['lines', 'contact', 'purchaseOrder']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function update(Bill $bill, User $user, array $data, array $lines = []): Bill
    {
        abort_unless($user->can('bills.update'), 403);

        if (! $bill->status->isEditable()) {
            throw ValidationException::withMessages([
                'bill' => [__('scf.purchase_workflow.document_not_editable')],
            ]);
        }

        return DB::transaction(function () use ($bill, $user, $data, $lines) {
            $totals = DocumentLineCalculator::summarize($lines);

            $bill->update([
                'reference_number' => $data['reference_number'] ?? $bill->reference_number,
                'contact_id' => $data['contact_id'] ?? $bill->contact_id,
                'purchase_order_id' => $data['purchase_order_id'] ?? $bill->purchase_order_id,
                'bill_date' => $data['bill_date'] ?? $bill->bill_date,
                'due_date' => $data['due_date'] ?? $bill->due_date,
                'subtotal_amount' => $totals['subtotal'] > 0 ? $totals['subtotal'] : ($data['subtotal_amount'] ?? $bill->subtotal_amount),
                'discount_amount' => $totals['discount'] > 0 ? $totals['discount'] : ($data['discount_amount'] ?? $bill->discount_amount),
                'tax_amount' => $totals['tax'] > 0 ? $totals['tax'] : ($data['tax_amount'] ?? $bill->tax_amount),
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? $bill->total_amount),
                'currency_code' => $data['currency_code'] ?? $bill->currency_code,
                'notes' => $data['notes'] ?? $bill->notes,
            ]);

            $bill->lines()->delete();
            foreach ($totals['lines'] as $line) {
                if (trim((string) ($line['description'] ?? '')) === '' && empty($line['product_id'])) {
                    continue;
                }
                $bill->lines()->create([
                    'product_id' => $line['product_id'] ?? null,
                    'purchase_order_line_id' => $line['purchase_order_line_id'] ?? null,
                    'line_number' => $line['line_number'],
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => $line['discount_amount'],
                    'tax_amount' => $line['tax_amount'],
                    'line_total' => $line['line_total'],
                ]);
            }

            $this->events->log($bill, 'updated', $user);

            return $bill->fresh(['lines', 'contact', 'purchaseOrder']);
        });
    }

    /**
     * Transitions bill to BillStatus::Received (the "issued" / posted state)
     * and posts the GL entry via AutoPostingService.
     */
    public function issue(Bill $bill, User $user): Bill
    {
        abort_unless($user->can('bills.issue') || $user->can('bills.approve'), 403);

        return DB::transaction(function () use ($bill, $user) {
            $locked = Bill::query()->whereKey($bill->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(BillStatus::Received)) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.purchase_workflow.invalid_transition', [
                        'from' => $locked->status->label(),
                        'to' => BillStatus::Received->label(),
                    ])],
                ]);
            }

            if ((float) $locked->total_amount <= 0) {
                throw ValidationException::withMessages([
                    'total_amount' => [__('scf.purchase_workflow.invalid_totals')],
                ]);
            }

            $from = $locked->status->value;
            $locked->update([
                'status' => BillStatus::Received,
                'issued_at' => now(),
            ]);

            $this->posting->postBill($locked->fresh(), $user);

            $this->events->log($locked, 'issued', $user, $from, BillStatus::Received->value, null, (float) $locked->total_amount);

            return $locked->fresh(['lines', 'contact', 'purchaseOrder', 'payments']);
        });
    }

    public function void(Bill $bill, User $user, ?string $reason = null): Bill
    {
        abort_unless($user->can('bills.void') || $user->can('bills.approve'), 403);

        return DB::transaction(function () use ($bill, $user, $reason) {
            $locked = Bill::query()->whereKey($bill->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(BillStatus::Void)) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.purchase_workflow.invalid_transition', [
                        'from' => $locked->status->label(),
                        'to' => BillStatus::Void->label(),
                    ])],
                ]);
            }

            if ((float) $locked->paid_amount > 0) {
                throw ValidationException::withMessages([
                    'bill' => [__('scf.purchase_workflow.cannot_void_with_payments')],
                ]);
            }

            $posting = AccountingPosting::query()
                ->where('source_type', $locked::class)
                ->where('source_id', $locked->id)
                ->where('event', 'bill.posted')
                ->first();

            if ($posting?->journalEntry) {
                $this->journals->reverse($posting->journalEntry, $user, $reason ?: 'Bill voided');
            }

            $from = $locked->status->value;
            $locked->update([
                'status' => BillStatus::Void,
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ]);

            $this->events->log($locked, 'voided', $user, $from, BillStatus::Void->value, $reason, (float) $locked->total_amount);

            return $locked->fresh();
        });
    }

    public function cancel(Bill $bill, User $user, ?string $reason = null): Bill
    {
        abort_unless($user->can('bills.update'), 403);

        return DB::transaction(function () use ($bill, $user, $reason) {
            $locked = Bill::query()->whereKey($bill->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== BillStatus::Draft) {
                throw ValidationException::withMessages([
                    'bill' => [__('scf.purchase_workflow.only_draft_cancellable')],
                ]);
            }

            $from = $locked->status->value;
            $locked->update(['status' => BillStatus::Cancelled]);
            $this->events->log($locked, 'cancelled', $user, $from, BillStatus::Cancelled->value, $reason);

            return $locked->fresh();
        });
    }

    public function refreshPaymentStatus(Bill $bill): Bill
    {
        $bill->load(['payments' => fn ($q) => $q
            ->where('status', 'posted')
            ->where('type', 'outgoing')
            ->whereNull('reversal_of_id')]);
        $paid = round((float) $bill->payments->sum('amount'), 2);
        $total = round((float) $bill->total_amount, 2);

        $status = $bill->status;
        if ($bill->status->isPosted()) {
            if ($paid <= 0) {
                if (in_array($bill->status, [BillStatus::PartiallyPaid, BillStatus::Overdue], true)) {
                    $status = ($bill->due_date && $bill->due_date->isPast())
                        ? BillStatus::Overdue
                        : BillStatus::Received;
                }
            } elseif ($paid + 0.001 >= $total) {
                $status = BillStatus::Paid;
            } else {
                $status = BillStatus::PartiallyPaid;
            }
        }

        $bill->update([
            'paid_amount' => $paid,
            'status' => $status,
        ]);

        return $bill->fresh();
    }

    public function markOverdueIfNeeded(Bill $bill): Bill
    {
        if (
            $bill->due_date
            && $bill->due_date->isPast()
            && in_array($bill->status, [BillStatus::Received, BillStatus::PartiallyPaid], true)
            && (float) $bill->paid_amount + 0.001 < (float) $bill->total_amount
        ) {
            $bill->update(['status' => BillStatus::Overdue]);
        }

        return $bill->fresh();
    }

    public function balanceDue(Bill $bill): float
    {
        return max(0, round((float) $bill->total_amount - (float) $bill->paid_amount, 2));
    }
}
