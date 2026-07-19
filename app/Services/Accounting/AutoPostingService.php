<?php

namespace App\Services\Accounting;

use App\Models\AccountingPosting;
use App\Models\Bill;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\PosSale;
use App\Models\User;
use App\Support\Accounting\JournalLineData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AutoPostingService
{
    public function __construct(
        protected JournalEngineService $journals,
        protected ChartOfAccountsService $accounts,
        protected AccountingAuditService $audit,
    ) {}

    public function postInvoice(Invoice $invoice, ?User $user = null): ?JournalEntry
    {
        if (($invoice->source ?? null) === 'pos') {
            return null;
        }

        return $this->idempotent($invoice, 'invoice.posted', $user, function (User $actor) use ($invoice) {
            $revenue = (float) $invoice->subtotal_amount > 0
                ? (float) $invoice->subtotal_amount
                : max(0, (float) $invoice->total_amount - (float) $invoice->tax_amount);
            $tax = (float) $invoice->tax_amount;
            $total = (float) $invoice->total_amount;
            $arId = $this->accounts->systemId('accounts_receivable');
            $salesId = $this->accounts->systemId('sales_revenue');
            $taxId = $this->accounts->systemId('tax_payable');

            $lines = [
                new JournalLineData($arId, number_format($total, 2, '.', ''), '0.00', $invoice->reference_number, $invoice->contact_id),
                new JournalLineData($salesId, '0.00', number_format($revenue, 2, '.', ''), $invoice->reference_number, $invoice->contact_id),
            ];

            if ($tax > 0) {
                $lines[] = new JournalLineData($taxId, '0.00', number_format($tax, 2, '.', ''), $invoice->reference_number);
            }

            return $this->journals->createDraft($actor, [
                'entry_date' => $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
                'description' => __('scf.accounting_engine.posting_invoice', ['ref' => $invoice->reference_number]),
                'reference' => $invoice,
                'idempotency_key' => $this->key($invoice, 'invoice.posted'),
                'auto_post' => true,
            ], $lines);
        });
    }

    public function postCustomerPayment(Payment $payment, ?User $user = null): ?JournalEntry
    {
        if ($payment->type !== \App\Enums\PaymentType::Incoming) {
            return null;
        }

        if ($this->isPosLinkedPayment($payment)) {
            return null;
        }

        return $this->idempotent($payment, 'payment.incoming', $user, function (User $actor) use ($payment) {
            $method = (string) ($payment->payment_method ?? 'cash');
            $cashKey = in_array($method, ['bank_transfer', 'card', 'bank'], true) ? 'bank' : 'cash';

            return $this->journals->createDraft($actor, [
                'entry_date' => $payment->payment_date?->toDateString() ?? now()->toDateString(),
                'description' => __('scf.accounting_engine.posting_customer_payment', ['ref' => $payment->reference_number]),
                'reference' => $payment,
                'idempotency_key' => $this->key($payment, 'payment.incoming'),
                'auto_post' => true,
            ], [
                new JournalLineData($this->accounts->systemId($cashKey), number_format((float) $payment->amount, 2, '.', ''), '0.00', $payment->reference_number, $payment->contact_id),
                new JournalLineData($this->accounts->systemId('accounts_receivable'), '0.00', number_format((float) $payment->amount, 2, '.', ''), $payment->reference_number, $payment->contact_id),
            ]);
        });
    }

    public function postBill(Bill $bill, ?User $user = null): ?JournalEntry
    {
        return $this->idempotent($bill, 'bill.posted', $user, function (User $actor) use ($bill) {
            $tax = (float) $bill->tax_amount;
            $net = max(0, (float) $bill->total_amount - $tax);
            $inventoryId = $this->accounts->systemId('inventory');
            $apId = $this->accounts->systemId('accounts_payable');
            $taxRecvId = $this->accounts->systemId('tax_receivable');

            $lines = [
                new JournalLineData($inventoryId, number_format($net, 2, '.', ''), '0.00', $bill->reference_number, $bill->contact_id),
                new JournalLineData($apId, '0.00', number_format((float) $bill->total_amount, 2, '.', ''), $bill->reference_number, $bill->contact_id),
            ];

            if ($tax > 0) {
                $lines[] = new JournalLineData($taxRecvId, number_format($tax, 2, '.', ''), '0.00', $bill->reference_number);
            }

            return $this->journals->createDraft($actor, [
                'entry_date' => $bill->bill_date?->toDateString() ?? now()->toDateString(),
                'description' => __('scf.accounting_engine.posting_bill', ['ref' => $bill->reference_number]),
                'reference' => $bill,
                'idempotency_key' => $this->key($bill, 'bill.posted'),
                'auto_post' => true,
            ], $lines);
        });
    }

    public function postSupplierPayment(Payment $payment, ?User $user = null): ?JournalEntry
    {
        if ($payment->type !== \App\Enums\PaymentType::Outgoing) {
            return null;
        }

        return $this->idempotent($payment, 'payment.outgoing', $user, function (User $actor) use ($payment) {
            $method = (string) ($payment->payment_method ?? 'cash');
            $cashKey = in_array($method, ['bank_transfer', 'card', 'bank'], true) ? 'bank' : 'cash';

            return $this->journals->createDraft($actor, [
                'entry_date' => $payment->payment_date?->toDateString() ?? now()->toDateString(),
                'description' => __('scf.accounting_engine.posting_supplier_payment', ['ref' => $payment->reference_number]),
                'reference' => $payment,
                'idempotency_key' => $this->key($payment, 'payment.outgoing'),
                'auto_post' => true,
            ], [
                new JournalLineData($this->accounts->systemId('accounts_payable'), number_format((float) $payment->amount, 2, '.', ''), '0.00', $payment->reference_number, $payment->contact_id),
                new JournalLineData($this->accounts->systemId($cashKey), '0.00', number_format((float) $payment->amount, 2, '.', ''), $payment->reference_number, $payment->contact_id),
            ]);
        });
    }

    public function postExpense(Expense $expense, ?User $user = null): ?JournalEntry
    {
        return $this->idempotent($expense, 'expense.posted', $user, function (User $actor) use ($expense) {
            return $this->journals->createDraft($actor, [
                'entry_date' => $expense->expense_date?->toDateString() ?? now()->toDateString(),
                'description' => __('scf.accounting_engine.posting_expense', ['ref' => $expense->reference_number]),
                'reference' => $expense,
                'idempotency_key' => $this->key($expense, 'expense.posted'),
                'auto_post' => true,
            ], [
                new JournalLineData($this->accounts->systemId('operating_expense'), number_format((float) $expense->amount, 2, '.', ''), '0.00', $expense->description, $expense->contact_id),
                new JournalLineData($this->accounts->systemId('cash'), '0.00', number_format((float) $expense->amount, 2, '.', ''), $expense->reference_number, $expense->contact_id),
            ]);
        });
    }

    public function postPosSale(PosSale $sale, ?User $user = null): ?JournalEntry
    {
        return $this->idempotent($sale, 'pos.sale', $user, function (User $actor) use ($sale) {
            $sale->loadMissing('items.product', 'payments');
            $revenue = max(0, (float) $sale->total_amount - (float) $sale->tax_amount);
            $tax = (float) $sale->tax_amount;
            $cogs = round($sale->items->sum(function ($item) {
                $cost = (float) ($item->product?->purchase_price ?? 0);

                return $cost * (float) $item->quantity;
            }), 2);

            $cashTotal = (float) $sale->payments->sum('amount');
            $cashAccount = $this->accounts->systemId('cash');
            if ($sale->payments->contains(fn ($p) => in_array($p->method?->value ?? (string) $p->method, ['card', 'bank_transfer', 'bank'], true))) {
                $cashAccount = $this->accounts->systemId('card_clearing');
            }

            $lines = [
                new JournalLineData($cashAccount, number_format($cashTotal, 2, '.', ''), '0.00', $sale->reference_number, $sale->contact_id),
                new JournalLineData($this->accounts->systemId('sales_revenue'), '0.00', number_format($revenue, 2, '.', ''), $sale->reference_number, $sale->contact_id),
            ];

            if ($tax > 0) {
                $lines[] = new JournalLineData($this->accounts->systemId('tax_payable'), '0.00', number_format($tax, 2, '.', ''), $sale->reference_number);
            }

            if ($cogs > 0) {
                $lines[] = new JournalLineData($this->accounts->systemId('cogs'), number_format($cogs, 2, '.', ''), '0.00', $sale->reference_number);
                $lines[] = new JournalLineData($this->accounts->systemId('inventory'), '0.00', number_format($cogs, 2, '.', ''), $sale->reference_number);
            }

            return $this->journals->createDraft($actor, [
                'entry_date' => $sale->created_at?->toDateString() ?? now()->toDateString(),
                'description' => __('scf.accounting_engine.posting_pos', ['ref' => $sale->reference_number]),
                'reference' => $sale,
                'idempotency_key' => $this->key($sale, 'pos.sale'),
                'auto_post' => true,
            ], $lines);
        });
    }

    public function postPayroll(Payroll $payroll, ?User $user = null): ?JournalEntry
    {
        return $this->idempotent($payroll, 'payroll.posted', $user, function (User $actor) use ($payroll) {
            $amount = (float) ($payroll->net_amount ?? $payroll->gross_amount ?? 0);

            return $this->journals->createDraft($actor, [
                'entry_date' => $payroll->pay_period_start?->toDateString() ?? now()->toDateString(),
                'description' => __('scf.accounting_engine.posting_payroll', ['ref' => $payroll->reference_number ?? $payroll->id]),
                'reference' => $payroll,
                'idempotency_key' => $this->key($payroll, 'payroll.posted'),
                'auto_post' => true,
            ], [
                new JournalLineData($this->accounts->systemId('payroll_expense'), number_format($amount, 2, '.', ''), '0.00'),
                new JournalLineData($this->accounts->systemId('payroll_payable'), '0.00', number_format($amount, 2, '.', '')),
            ]);
        });
    }

    /**
     * @param  callable(User): JournalEntry  $callback
     */
    protected function idempotent(Model $source, string $event, ?User $user, callable $callback): ?JournalEntry
    {
        if (! config('accounting.auto_post', true)) {
            return null;
        }

        $existing = AccountingPosting::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->getKey())
            ->where('event', $event)
            ->first();

        if ($existing) {
            return $existing->journalEntry;
        }

        $actor = $user ?? auth()->user() ?? User::query()->orderBy('id')->first();
        if ($actor === null) {
            return null;
        }

        return DB::transaction(function () use ($source, $event, $actor, $callback) {
            $again = AccountingPosting::query()
                ->where('source_type', $source::class)
                ->where('source_id', $source->getKey())
                ->where('event', $event)
                ->lockForUpdate()
                ->first();

            if ($again) {
                return $again->journalEntry;
            }

            $entry = $callback($actor);

            AccountingPosting::query()->create([
                'source_type' => $source::class,
                'source_id' => $source->getKey(),
                'event' => $event,
                'journal_entry_id' => $entry->id,
                'idempotency_key' => $this->key($source, $event),
            ]);

            $this->audit->log('auto_post.'.$event, $entry, $actor, [
                'source_type' => $source::class,
                'source_id' => $source->getKey(),
            ]);

            return $entry;
        });
    }

    protected function key(Model $source, string $event): string
    {
        return Str::of($source::class)->classBasename().':'.$source->getKey().':'.$event;
    }

    protected function isPosLinkedPayment(Payment $payment): bool
    {
        if (! $payment->invoice_id) {
            return false;
        }

        return Invoice::query()
            ->whereKey($payment->invoice_id)
            ->where('source', 'pos')
            ->exists();
    }
}
