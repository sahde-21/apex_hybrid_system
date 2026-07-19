<?php

use App\Enums\InvoiceStatus;
use App\Enums\JournalEntryStatus;
use App\Enums\PaymentType;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Services\Accounting\AutoPostingService;
use App\Services\Accounting\FinancialStatementService;
use App\Services\Accounting\JournalEngineService;
use App\Support\Accounting\JournalLineData;
use Database\Seeders\AccountingSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    test()->seed(AccountingSeeder::class);
});

function cashAccount(): Account
{
    return Account::query()->where('system_key', 'cash')->firstOrFail();
}

function salesAccount(): Account
{
    return Account::query()->where('system_key', 'sales_revenue')->firstOrFail();
}

test('unbalanced journals are rejected', function () {
    $user = actingAsSuperAdmin();

    expect(fn () => app(JournalEngineService::class)->createDraft($user, [
        'entry_date' => now()->toDateString(),
        'description' => 'Bad entry',
    ], [
        new JournalLineData(cashAccount()->id, '100.00', '0.00'),
        new JournalLineData(salesAccount()->id, '0.00', '50.00'),
    ]))->toThrow(ValidationException::class);
});

test('balanced journals can be posted and become immutable', function () {
    $user = actingAsSuperAdmin();
    $engine = app(JournalEngineService::class);

    $entry = $engine->createDraft($user, [
        'entry_date' => now()->toDateString(),
        'description' => 'Balanced entry',
    ], [
        new JournalLineData(cashAccount()->id, '100.00', '0.00'),
        new JournalLineData(salesAccount()->id, '0.00', '100.00'),
    ]);

    expect($entry->status)->toBe(JournalEntryStatus::Draft)
        ->and($entry->isBalanced())->toBeTrue();

    $posted = $engine->post($entry, $user);
    expect($posted->status)->toBe(JournalEntryStatus::Posted);

    expect(fn () => $engine->updateDraft($posted, $user, [
        'entry_date' => now()->toDateString(),
        'description' => 'Nope',
    ], [
        new JournalLineData(cashAccount()->id, '100.00', '0.00'),
        new JournalLineData(salesAccount()->id, '0.00', '100.00'),
    ]))->toThrow(ValidationException::class);
});

test('posted journals can be reversed', function () {
    $user = actingAsSuperAdmin();
    $engine = app(JournalEngineService::class);

    $entry = $engine->createDraft($user, [
        'entry_date' => now()->toDateString(),
        'description' => 'To reverse',
        'auto_post' => true,
    ], [
        new JournalLineData(cashAccount()->id, '40.00', '0.00'),
        new JournalLineData(salesAccount()->id, '0.00', '40.00'),
    ]);

    $reversal = $engine->reverse($entry, $user, 'Correction');

    expect($entry->fresh()->status)->toBe(JournalEntryStatus::Reversed)
        ->and($reversal->status)->toBe(JournalEntryStatus::Posted)
        ->and($reversal->reversal_of_id)->toBe($entry->id);
});

test('invoice auto posting creates balanced journal', function () {
    $user = actingAsSuperAdmin();
    $contact = Contact::factory()->create();

    $invoice = Invoice::query()->create([
        'reference_number' => 'INV-ACC-1',
        'contact_id' => $contact->id,
        'invoice_date' => now()->toDateString(),
        'status' => InvoiceStatus::Sent,
        'subtotal_amount' => 100,
        'tax_amount' => 10,
        'discount_amount' => 0,
        'total_amount' => 110,
        'source' => 'manual',
    ]);

    $entry = app(AutoPostingService::class)->postInvoice($invoice, $user);

    expect($entry)->toBeInstanceOf(JournalEntry::class)
        ->and($entry->status)->toBe(JournalEntryStatus::Posted)
        ->and($entry->isBalanced())->toBeTrue();

    // Idempotent
    $again = app(AutoPostingService::class)->postInvoice($invoice, $user);
    expect($again->id)->toBe($entry->id);
});

test('customer payment auto posting works', function () {
    $user = actingAsSuperAdmin();
    $contact = Contact::factory()->create();

    $payment = Payment::query()->create([
        'reference_number' => 'PAY-ACC-1',
        'contact_id' => $contact->id,
        'payment_date' => now()->toDateString(),
        'amount' => 55,
        'type' => PaymentType::Incoming,
        'payment_method' => 'cash',
    ]);

    $entry = app(AutoPostingService::class)->postCustomerPayment($payment, $user);

    expect($entry)->not->toBeNull()
        ->and($entry->isBalanced())->toBeTrue()
        ->and($entry->status)->toBe(JournalEntryStatus::Posted);
});

test('expense auto posting works', function () {
    $user = actingAsSuperAdmin();

    $expense = Expense::query()->create([
        'reference_number' => 'EXP-ACC-1',
        'category' => 'ops',
        'description' => 'Office',
        'amount' => 25,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
    ]);

    $entry = app(AutoPostingService::class)->postExpense($expense, $user);

    expect($entry)->not->toBeNull()->and($entry->isBalanced())->toBeTrue();
});

test('trial balance is balanced after postings', function () {
    $user = actingAsSuperAdmin();
    app(JournalEngineService::class)->createDraft($user, [
        'entry_date' => now()->toDateString(),
        'description' => 'TB seed',
        'auto_post' => true,
    ], [
        new JournalLineData(cashAccount()->id, '200.00', '0.00'),
        new JournalLineData(salesAccount()->id, '0.00', '200.00'),
    ]);

    $tb = app(FinancialStatementService::class)->trialBalance([
        'from' => now()->startOfMonth()->toDateString(),
        'to' => now()->toDateString(),
    ]);

    expect($tb['balanced'])->toBeTrue()
        ->and(bccomp($tb['total_debit'], $tb['total_credit'], 2))->toBe(0);
});

test('users without journal permission cannot open chart of accounts', function () {
    actingAsUserWithPermissions(['dashboard.read']);

    $this->get(route('chart-of-accounts.index'))->assertForbidden();
});

test('authorized users can open accounting workspace pages', function () {
    actingAsSuperAdmin();

    $this->get(route('chart-of-accounts.index'))->assertOk();
    $this->get(route('ledger.index'))->assertOk();
    $this->get(route('statements.index'))->assertOk();
    $this->get(route('journal-entries.create'))->assertOk();
});

test('accounting translations exist', function () {
    foreach (['en'] as $locale) {
        app()->setLocale($locale);
        expect(__('scf.accounting_engine.coa_title'))->not->toBe('scf.accounting_engine.coa_title')
            ->and(__('scf.accounting_engine.trial_balance'))->not->toBe('scf.accounting_engine.trial_balance');
    }
});
