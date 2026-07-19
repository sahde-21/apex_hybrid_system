<?php

namespace App\Services\Sales;

use App\Enums\InvoiceStatus;
use App\Models\AccountingPosting;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Accounting\AutoPostingService;
use App\Services\Accounting\JournalEngineService;
use App\Support\Sales\DocumentLineCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceWorkflowService
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
    public function create(User $user, array $data, array $lines = []): Invoice
    {
        abort_unless($user->can('invoices.create'), 403);

        return DB::transaction(function () use ($user, $data, $lines) {
            $totals = DocumentLineCalculator::summarize($lines);

            $invoice = Invoice::query()->create([
                'reference_number' => $data['reference_number'],
                'contact_id' => $data['contact_id'] ?? null,
                'sale_order_id' => $data['sale_order_id'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'status' => InvoiceStatus::Draft,
                'subtotal_amount' => $totals['subtotal'] > 0 ? $totals['subtotal'] : ($data['subtotal_amount'] ?? 0),
                'discount_amount' => $totals['discount'] > 0 ? $totals['discount'] : ($data['discount_amount'] ?? 0),
                'tax_amount' => $totals['tax'] > 0 ? $totals['tax'] : ($data['tax_amount'] ?? 0),
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? 0),
                'paid_amount' => 0,
                'currency_code' => $data['currency_code'] ?? config('accounting.base_currency', 'IQD'),
                'payment_terms' => $data['payment_terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'source' => $data['source'] ?? 'manual',
            ]);

            foreach ($totals['lines'] as $line) {
                if (trim((string) ($line['description'] ?? '')) === '' && empty($line['product_id'])) {
                    continue;
                }
                $invoice->lines()->create($line);
            }

            $this->events->log($invoice, 'created', $user, null, InvoiceStatus::Draft->value);

            return $invoice->fresh(['lines', 'contact', 'saleOrder']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function update(Invoice $invoice, User $user, array $data, array $lines = []): Invoice
    {
        abort_unless($user->can('invoices.update'), 403);

        if (! $invoice->status->isEditable()) {
            throw ValidationException::withMessages([
                'invoice' => [__('scf.sales_workflow.document_not_editable')],
            ]);
        }

        return DB::transaction(function () use ($invoice, $user, $data, $lines) {
            $totals = DocumentLineCalculator::summarize($lines);

            $invoice->update([
                'reference_number' => $data['reference_number'] ?? $invoice->reference_number,
                'contact_id' => $data['contact_id'] ?? $invoice->contact_id,
                'sale_order_id' => $data['sale_order_id'] ?? $invoice->sale_order_id,
                'invoice_date' => $data['invoice_date'] ?? $invoice->invoice_date,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'subtotal_amount' => $totals['subtotal'] > 0 ? $totals['subtotal'] : ($data['subtotal_amount'] ?? $invoice->subtotal_amount),
                'discount_amount' => $totals['discount'] > 0 ? $totals['discount'] : ($data['discount_amount'] ?? $invoice->discount_amount),
                'tax_amount' => $totals['tax'] > 0 ? $totals['tax'] : ($data['tax_amount'] ?? $invoice->tax_amount),
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? $invoice->total_amount),
                'currency_code' => $data['currency_code'] ?? $invoice->currency_code,
                'payment_terms' => $data['payment_terms'] ?? $invoice->payment_terms,
                'notes' => $data['notes'] ?? $invoice->notes,
            ]);

            $invoice->lines()->delete();
            foreach ($totals['lines'] as $line) {
                if (trim((string) ($line['description'] ?? '')) === '' && empty($line['product_id'])) {
                    continue;
                }
                $invoice->lines()->create($line);
            }

            $this->events->log($invoice, 'updated', $user);

            return $invoice->fresh(['lines', 'contact', 'saleOrder']);
        });
    }

    public function issue(Invoice $invoice, User $user): Invoice
    {
        abort_unless($user->can('invoices.issue') || $user->can('invoices.approve'), 403);

        return DB::transaction(function () use ($invoice, $user) {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(InvoiceStatus::Sent)) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.sales_workflow.invalid_transition', [
                        'from' => $locked->status->label(),
                        'to' => InvoiceStatus::Sent->label(),
                    ])],
                ]);
            }

            if ((float) $locked->total_amount <= 0) {
                throw ValidationException::withMessages([
                    'total_amount' => [__('scf.sales_workflow.invalid_totals')],
                ]);
            }

            if (! $locked->contact_id) {
                throw ValidationException::withMessages([
                    'contact_id' => [__('scf.sales_workflow.customer_required')],
                ]);
            }

            $from = $locked->status->value;
            $locked->update([
                'status' => InvoiceStatus::Sent,
                'issued_at' => now(),
            ]);

            // Post to GL via existing engine (respects fiscal period)
            $this->posting->postInvoice($locked->fresh(), $user);

            $this->events->log($locked, 'issued', $user, $from, InvoiceStatus::Sent->value, null, (float) $locked->total_amount);

            return $locked->fresh(['lines', 'contact', 'saleOrder', 'payments']);
        });
    }

    public function void(Invoice $invoice, User $user, ?string $reason = null): Invoice
    {
        abort_unless($user->can('invoices.void') || $user->can('invoices.approve'), 403);

        return DB::transaction(function () use ($invoice, $user, $reason) {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(InvoiceStatus::Void)) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.sales_workflow.invalid_transition', [
                        'from' => $locked->status->label(),
                        'to' => InvoiceStatus::Void->label(),
                    ])],
                ]);
            }

            if ((float) $locked->paid_amount > 0) {
                throw ValidationException::withMessages([
                    'invoice' => [__('scf.sales_workflow.cannot_void_with_payments')],
                ]);
            }

            $posting = AccountingPosting::query()
                ->where('source_type', $locked::class)
                ->where('source_id', $locked->id)
                ->where('event', 'invoice.posted')
                ->first();

            if ($posting?->journal_entry_id) {
                $entry = $posting->journalEntry;
                if ($entry) {
                    $this->journals->reverse($entry, $user, $reason ?: 'Invoice voided');
                }
            }

            $from = $locked->status->value;
            $locked->update([
                'status' => InvoiceStatus::Void,
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ]);

            $this->events->log($locked, 'voided', $user, $from, InvoiceStatus::Void->value, $reason, (float) $locked->total_amount);

            return $locked->fresh();
        });
    }

    public function cancel(Invoice $invoice, User $user, ?string $reason = null): Invoice
    {
        abort_unless($user->can('invoices.update'), 403);

        return DB::transaction(function () use ($invoice, $user, $reason) {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== InvoiceStatus::Draft) {
                throw ValidationException::withMessages([
                    'invoice' => [__('scf.sales_workflow.only_draft_cancellable')],
                ]);
            }

            $from = $locked->status->value;
            $locked->update(['status' => InvoiceStatus::Cancelled]);
            $this->events->log($locked, 'cancelled', $user, $from, InvoiceStatus::Cancelled->value, $reason);

            return $locked->fresh();
        });
    }

    public function refreshPaymentStatus(Invoice $invoice): Invoice
    {
        $invoice->load(['payments' => fn ($q) => $q
            ->where('status', 'posted')
            ->where('type', 'incoming')
            ->whereNull('reversal_of_id')]);
        $paid = round((float) $invoice->payments->sum('amount'), 2);
        $total = round((float) $invoice->total_amount, 2);

        $status = $invoice->status;
        if ($invoice->status->isPosted() || $invoice->status === InvoiceStatus::Draft) {
            if ($paid <= 0) {
                if ($invoice->status === InvoiceStatus::Sent || $invoice->status === InvoiceStatus::PartiallyPaid || $invoice->status === InvoiceStatus::Overdue) {
                    $status = ($invoice->due_date && $invoice->due_date->isPast())
                        ? InvoiceStatus::Overdue
                        : InvoiceStatus::Sent;
                }
            } elseif ($paid + 0.001 >= $total) {
                $status = InvoiceStatus::Paid;
            } else {
                $status = InvoiceStatus::PartiallyPaid;
            }
        }

        $invoice->update([
            'paid_amount' => $paid,
            'status' => $status,
        ]);

        return $invoice->fresh();
    }

    public function markOverdueIfNeeded(Invoice $invoice): Invoice
    {
        if (
            $invoice->due_date
            && $invoice->due_date->isPast()
            && in_array($invoice->status, [InvoiceStatus::Sent, InvoiceStatus::PartiallyPaid], true)
            && (float) $invoice->paid_amount + 0.001 < (float) $invoice->total_amount
        ) {
            $invoice->update(['status' => InvoiceStatus::Overdue]);
        }

        return $invoice->fresh();
    }

    public function balanceDue(Invoice $invoice): float
    {
        return max(0, round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2));
    }
}
