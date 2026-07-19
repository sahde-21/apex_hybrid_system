<?php

use App\Enums\AccountType;
use App\Enums\FiscalPeriodStatus;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\AccountingAuditLog;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\CurrencyService;
use App\Services\Accounting\FiscalPeriodService;
use App\Services\Accounting\JournalEngineService;
use App\Support\Accounting\JournalLineData;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    test()->seed(RolePermissionSeeder::class);
    test()->seed(AccountingSeeder::class);
});

function fiscalYear(): FiscalYear
{
    return FiscalYear::query()->firstOrFail();
}

function openPeriod(): FiscalPeriod
{
    return FiscalPeriod::query()->where('status', FiscalPeriodStatus::Open)->orderBy('period_number')->firstOrFail();
}

test('fiscal period index requires permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('fiscal-periods.index'))
        ->assertForbidden();
});

test('accountant can view and manage fiscal periods', function () {
    $user = actingAsRole('accountant');

    $this->get(route('fiscal-periods.index'))->assertOk();
    $this->get(route('fiscal-periods.create'))->assertOk();

    $service = app(FiscalPeriodService::class);
    $year = fiscalYear();

    $period = $service->create($user, [
        'fiscal_year_id' => $year->id,
        'name' => 'P-13',
        'period_number' => 13,
        'starts_on' => now()->addYear()->startOfYear()->toDateString(),
        'ends_on' => now()->addYear()->startOfYear()->endOfMonth()->toDateString(),
    ]);

    expect($period->status)->toBe(FiscalPeriodStatus::Open)
        ->and(AccountingAuditLog::query()->where('action', 'period.created')->exists())->toBeTrue();

    $closed = $service->closePeriod($period, $user);
    expect($closed->status)->toBe(FiscalPeriodStatus::Closed);

    $reopened = $service->reopenPeriod($closed, $user);
    expect($reopened->status)->toBe(FiscalPeriodStatus::Open);

    $locked = $service->lockPeriod($reopened, $user);
    expect($locked->status)->toBe(FiscalPeriodStatus::Locked);
});

test('posting into a closed period is blocked', function () {
    $user = actingAsSuperAdmin();
    $period = openPeriod();
    app(FiscalPeriodService::class)->closePeriod($period, $user);

    $cash = Account::query()->where('system_key', 'cash')->firstOrFail();
    $sales = Account::query()->where('system_key', 'sales_revenue')->firstOrFail();

    expect(fn () => app(JournalEngineService::class)->createDraft($user, [
        'entry_date' => $period->starts_on->toDateString(),
        'description' => 'Blocked',
    ], [
        new JournalLineData($cash->id, '10.00', '0.00'),
        new JournalLineData($sales->id, '0.00', '10.00'),
    ]))->toThrow(ValidationException::class);
});

test('posting into a locked period is blocked', function () {
    $user = actingAsSuperAdmin();
    $period = openPeriod();
    app(FiscalPeriodService::class)->lockPeriod($period, $user);

    $cash = Account::query()->where('system_key', 'cash')->firstOrFail();
    $sales = Account::query()->where('system_key', 'sales_revenue')->firstOrFail();

    expect(fn () => app(JournalEngineService::class)->createDraft($user, [
        'entry_date' => $period->starts_on->toDateString(),
        'description' => 'Locked block',
    ], [
        new JournalLineData($cash->id, '10.00', '0.00'),
        new JournalLineData($sales->id, '0.00', '10.00'),
    ]))->toThrow(ValidationException::class);
});

test('currency crud and exchange rates work', function () {
    $user = actingAsRole('accountant');
    $service = app(CurrencyService::class);

    $this->get(route('currencies.index'))->assertOk();

    $eur = $service->create($user, [
        'code' => 'EUR',
        'name' => 'Euro',
        'symbol' => '€',
        'decimal_places' => 2,
        'is_base' => false,
        'is_active' => true,
    ]);

    expect($eur->code)->toBe('EUR');

    $rate = $service->upsertExchangeRate($eur, $user, [
        'rate_date' => now()->toDateString(),
        'rate' => 0.00075,
    ]);

    expect($rate->rate)->toBe('0.00075000')
        ->and(ExchangeRate::query()->where('currency_id', $eur->id)->count())->toBe(1);

    $historical = $service->upsertExchangeRate($eur, $user, [
        'rate_date' => now()->subDay()->toDateString(),
        'rate' => 0.00070,
    ]);

    expect(ExchangeRate::query()->where('currency_id', $eur->id)->count())->toBe(2)
        ->and($service->rateFor($eur, now()->toDateString())?->id)->toBe($rate->id);

    $service->deleteExchangeRate($historical, $user);
    expect(ExchangeRate::query()->where('currency_id', $eur->id)->count())->toBe(1);

    $this->get(route('currencies.rates', $eur))->assertOk();
});

test('base currency cannot be deleted or deactivated', function () {
    $user = actingAsSuperAdmin();
    $base = Currency::query()->where('is_base', true)->firstOrFail();
    $service = app(CurrencyService::class);

    expect(fn () => $service->delete($base, $user))->toThrow(ValidationException::class);
    expect(fn () => $service->update($base, $user, ['is_active' => false]))->toThrow(ValidationException::class);
});

test('chart of accounts create update archive restore and delete', function () {
    $user = actingAsRole('accountant');
    $coa = app(ChartOfAccountsService::class);

    $this->get(route('chart-of-accounts.index'))->assertOk();
    $this->get(route('chart-of-accounts.create'))->assertOk();

    $parent = $coa->create($user, [
        'code' => '9900',
        'name' => 'Custom Parent',
        'type' => AccountType::Asset->value,
        'normal_balance' => NormalBalance::Debit->value,
        'opening_balance' => 100,
        'is_active' => true,
        'allow_manual_entry' => true,
    ]);

    $child = $coa->create($user, [
        'code' => '9901',
        'name' => 'Custom Child',
        'parent_id' => $parent->id,
        'type' => AccountType::Asset->value,
        'opening_balance' => 25.5,
    ]);

    expect($child->parent_id)->toBe($parent->id)
        ->and((float) $child->opening_balance)->toBe(25.5);

    $updated = $coa->update($child, $user, [
        'name' => 'Custom Child Updated',
        'opening_balance' => 40,
    ]);
    expect($updated->name)->toBe('Custom Child Updated');

    $this->get(route('chart-of-accounts.edit', $child))->assertOk();

    $coa->archive($child, $user);
    expect(Account::query()->find($child->id))->toBeNull()
        ->and(Account::onlyTrashed()->find($child->id))->not->toBeNull();

    $restored = $coa->restore(Account::onlyTrashed()->findOrFail($child->id), $user);
    expect($restored->trashed())->toBeFalse()->and($restored->is_active)->toBeTrue();

    $coa->delete($restored, $user);
    expect(Account::withTrashed()->find($child->id))->toBeNull();

    expect(fn () => $coa->delete($parent, $user))->not->toThrow(ValidationException::class);
});

test('system accounts cannot be archived or deleted', function () {
    $user = actingAsSuperAdmin();
    $cash = Account::query()->where('system_key', 'cash')->firstOrFail();
    $coa = app(ChartOfAccountsService::class);

    expect(fn () => $coa->archive($cash, $user))->toThrow(ValidationException::class);
    expect(fn () => $coa->delete($cash, $user))->toThrow(ValidationException::class);
});

test('users without permissions cannot access coa or currency pages', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('chart-of-accounts.index'))->assertForbidden();
    $this->get(route('chart-of-accounts.create'))->assertForbidden();
    $this->get(route('currencies.index'))->assertForbidden();
    $this->get(route('fiscal-periods.index'))->assertForbidden();
});

test('cashier cannot manage fiscal periods', function () {
    $user = actingAsRole('cashier');
    $period = openPeriod();

    expect(fn () => app(FiscalPeriodService::class)->closePeriod($period, $user))
        ->toThrow(HttpException::class);
});
