<?php

namespace App\Services\Sales;

use App\Enums\BillStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\AccountingPosting;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Accounting\AutoPostingService;
use App\Services\Accounting\JournalEngineService;
use App\Services\Purchasing\BillWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentWorkflowService
{
    public function __construct(
        protected SalesDocumentEventLogger $events,
        protected InvoiceWorkflowService $invoices,
        protected AutoPostingService $posting,
        protected JournalEngineService $journals,
        protected BillWorkflowService $bills,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Payment
    {
        abort_unless($user->can('payments.create') || $user->can('payments.record'), 403);

        return DB::transaction(function () use ($user, $data): Payment {
            $invoice = null;
            if (! empty($data['invoice_id'])) {
                /** @var Invoice $invoice */
                $invoice = Invoice::query()->whereKey($data['invoice_id'])->lockForUpdate()->firstOrFail();
                $this->assertInvoicePayable($invoice, (float) $data['amount']);
            }

            $bill = null;
            if (! empty($data['bill_id'])) {
                /** @var Bill $bill */
                $bill = Bill::query()->whereKey($data['bill_id'])->lockForUpdate()->firstOrFail();
                $this->assertBillPayable($bill, (float) $data['amount']);
            }

            $payment = Payment::query()->create([
                'reference_number' => $data['reference_number'],
                'contact_id' => $data['contact_id'] ?? ($invoice !== null ? $invoice->contact_id : null) ?? ($bill !== null ? $bill->contact_id : null),
                'invoice_id' => $invoice?->id,
                'bill_id' => $bill?->id,
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'type' => $data['type'] ?? ($bill ? PaymentType::Outgoing->value : PaymentType::Incoming->value),
                'status' => PaymentStatus::Draft,
                'payment_method' => $data['payment_method'] ?? null,
                'account_label' => $data['account_label'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->events->log($payment, 'created', $user, null, PaymentStatus::Draft->value, null, (float) $payment->amount, $invoice ?? $bill);

            return $payment->fresh(['invoice', 'bill', 'contact']);
        });
    }

    public function post(Payment $payment, User $user): Payment
    {
        abort_unless($user->can('payments.post') || $user->can('payments.update'), 403);

        return DB::transaction(function () use ($payment, $user): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(PaymentStatus::Posted)) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.sales_workflow.invalid_transition', [
                        'from' => $locked->status->label(),
                        'to' => PaymentStatus::Posted->label(),
                    ])],
                ]);
            }

            if ($locked->invoice_id) {
                $invoice = Invoice::query()->lockForUpdate()->findOrFail($locked->invoice_id);
                $this->assertInvoicePayable($invoice, (float) $locked->amount);
            }

            if ($locked->bill_id) {
                $bill = Bill::query()->lockForUpdate()->findOrFail($locked->bill_id);
                $this->assertBillPayable($bill, (float) $locked->amount);
            }

            $from = $locked->status->value;
            $locked->update([
                'status' => PaymentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $user->id,
            ]);

            if ($locked->type === PaymentType::Incoming) {
                $this->posting->postCustomerPayment($locked->fresh(), $user);
            } else {
                $this->posting->postSupplierPayment($locked->fresh(), $user);
            }

            if ($locked->invoice_id) {
                $this->invoices->refreshPaymentStatus(Invoice::query()->findOrFail($locked->invoice_id));
            }

            if ($locked->bill_id) {
                $this->bills->refreshPaymentStatus(Bill::query()->findOrFail($locked->bill_id));
            }

            $this->events->log($locked, 'posted', $user, $from, PaymentStatus::Posted->value, null, (float) $locked->amount, $locked->invoice ?? $locked->bill);

            return $locked->fresh(['invoice', 'bill', 'contact']);
        });
    }

    public function reverse(Payment $payment, User $user, string $reason): Payment
    {
        abort_unless($user->can('payments.reverse') || $user->can('payments.approve'), 403);

        return DB::transaction(function () use ($payment, $user, $reason): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(PaymentStatus::Reversed)) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.sales_workflow.invalid_transition', [
                        'from' => $locked->status->label(),
                        'to' => PaymentStatus::Reversed->label(),
                    ])],
                ]);
            }

            $reversal = Payment::query()->create([
                'reference_number' => $locked->reference_number.'-REV',
                'contact_id' => $locked->contact_id,
                'invoice_id' => $locked->invoice_id,
                'bill_id' => $locked->bill_id,
                'payment_date' => now()->toDateString(),
                'amount' => $locked->amount,
                'type' => $locked->type === PaymentType::Incoming ? PaymentType::Outgoing : PaymentType::Incoming,
                'status' => PaymentStatus::Posted,
                'payment_method' => $locked->payment_method,
                'account_label' => $locked->account_label,
                'notes' => $reason,
                'posted_at' => now(),
                'posted_by' => $user->id,
                'reversal_of_id' => $locked->id,
                'reversal_reason' => $reason,
            ]);

            $posting = AccountingPosting::query()
                ->where('source_type', $locked::class)
                ->where('source_id', $locked->id)
                ->first();

            if ($posting?->journalEntry) {
                $this->journals->reverse($posting->journalEntry, $user, $reason);
            }

            $from = $locked->status->value;
            $locked->update([
                'status' => PaymentStatus::Reversed,
                'reversed_at' => now(),
                'reversed_by' => $user->id,
                'reversal_reason' => $reason,
            ]);

            if ($locked->invoice_id) {
                $this->invoices->refreshPaymentStatus(Invoice::query()->findOrFail($locked->invoice_id));
            }

            if ($locked->bill_id) {
                $this->bills->refreshPaymentStatus(Bill::query()->findOrFail($locked->bill_id));
            }

            $this->events->log($locked, 'reversed', $user, $from, PaymentStatus::Reversed->value, $reason, (float) $locked->amount, $reversal);

            return $locked->fresh(['invoice', 'bill', 'contact']);
        });
    }

    public function cancel(Payment $payment, User $user): Payment
    {
        abort_unless($user->can('payments.update'), 403);

        return DB::transaction(function () use ($payment, $user): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PaymentStatus::Draft) {
                throw ValidationException::withMessages([
                    'payment' => [__('scf.sales_workflow.only_draft_cancellable')],
                ]);
            }

            $from = $locked->status->value;
            $locked->update(['status' => PaymentStatus::Cancelled]);
            $this->events->log($locked, 'cancelled', $user, $from, PaymentStatus::Cancelled->value);

            return $locked->fresh();
        });
    }

    protected function assertInvoicePayable(Invoice $invoice, float $amount): void
    {
        if ($invoice->status === InvoiceStatus::Draft) {
            throw ValidationException::withMessages([
                'invoice_id' => [__('scf.sales_workflow.invoice_must_be_issued')],
            ]);
        }

        if (in_array($invoice->status, [InvoiceStatus::Void, InvoiceStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'invoice_id' => [__('scf.sales_workflow.invoice_not_payable')],
            ]);
        }

        if ($invoice->status === InvoiceStatus::Paid) {
            throw ValidationException::withMessages([
                'amount' => [__('scf.sales_workflow.overpayment_blocked')],
            ]);
        }

        if (! $invoice->status->isPosted()) {
            throw ValidationException::withMessages([
                'invoice_id' => [__('scf.sales_workflow.invoice_not_payable')],
            ]);
        }

        $balance = max(0, round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2));

        if ($amount - $balance > 0.01) {
            throw ValidationException::withMessages([
                'amount' => [__('scf.sales_workflow.overpayment_blocked')],
            ]);
        }
    }

    protected function assertBillPayable(Bill $bill, float $amount): void
    {
        if ($bill->status === BillStatus::Draft) {
            throw ValidationException::withMessages([
                'bill_id' => [__('scf.purchase_workflow.bill_must_be_issued')],
            ]);
        }

        if (in_array($bill->status, [BillStatus::Void, BillStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'bill_id' => [__('scf.purchase_workflow.bill_not_payable')],
            ]);
        }

        if ($bill->status === BillStatus::Paid) {
            throw ValidationException::withMessages([
                'amount' => [__('scf.purchase_workflow.overpayment_blocked')],
            ]);
        }

        if (! $bill->status->isPosted()) {
            throw ValidationException::withMessages([
                'bill_id' => [__('scf.purchase_workflow.bill_not_payable')],
            ]);
        }

        $balance = max(0, round((float) $bill->total_amount - (float) $bill->paid_amount, 2));

        if ($amount - $balance > 0.01) {
            throw ValidationException::withMessages([
                'amount' => [__('scf.purchase_workflow.overpayment_blocked')],
            ]);
        }
    }
}
