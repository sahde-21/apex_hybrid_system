<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\FiscalPeriodStatus;
use App\Enums\FiscalYearStatus;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\Currency;
use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $base = config('accounting.base_currency', 'IQD');

        Currency::query()->firstOrCreate(
            ['code' => $base],
            ['name' => $base, 'symbol' => $base, 'decimal_places' => 2, 'is_base' => true, 'is_active' => true]
        );

        Currency::query()->firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_base' => false, 'is_active' => true]
        );

        $year = FiscalYear::query()->firstOrCreate(
            [
                'starts_on' => now()->startOfYear()->toDateString(),
                'ends_on' => now()->endOfYear()->toDateString(),
            ],
            [
                'name' => (string) now()->year,
                'status' => FiscalYearStatus::Open,
            ]
        );

        for ($month = 1; $month <= 12; $month++) {
            $start = now()->startOfYear()->month($month)->startOfMonth();
            FiscalPeriod::query()->firstOrCreate(
                [
                    'fiscal_year_id' => $year->id,
                    'period_number' => $month,
                ],
                [
                    'name' => $start->format('Y-m'),
                    'starts_on' => $start->toDateString(),
                    'ends_on' => $start->copy()->endOfMonth()->toDateString(),
                    'status' => FiscalPeriodStatus::Open,
                ]
            );
        }

        foreach ($this->defaultAccounts($base) as $account) {
            Account::query()->updateOrCreate(
                ['system_key' => $account['system_key']],
                $account
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function defaultAccounts(string $currency): array
    {
        $defs = [
            ['code' => '1000', 'name' => 'Cash', 'type' => AccountType::Asset, 'key' => 'cash'],
            ['code' => '1010', 'name' => 'Bank', 'type' => AccountType::Asset, 'key' => 'bank'],
            ['code' => '1020', 'name' => 'Card Clearing', 'type' => AccountType::Asset, 'key' => 'card_clearing'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => AccountType::Asset, 'key' => 'accounts_receivable'],
            ['code' => '1200', 'name' => 'Inventory', 'type' => AccountType::Asset, 'key' => 'inventory'],
            ['code' => '1300', 'name' => 'Tax Receivable', 'type' => AccountType::Asset, 'key' => 'tax_receivable'],
            ['code' => '1500', 'name' => 'Fixed Assets', 'type' => AccountType::Asset, 'key' => 'fixed_assets'],
            ['code' => '1510', 'name' => 'Accumulated Depreciation', 'type' => AccountType::Asset, 'key' => 'accumulated_depreciation', 'normal' => NormalBalance::Credit],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => AccountType::Liability, 'key' => 'accounts_payable'],
            ['code' => '2100', 'name' => 'Tax Payable', 'type' => AccountType::Liability, 'key' => 'tax_payable'],
            ['code' => '2200', 'name' => 'Payroll Payable', 'type' => AccountType::Liability, 'key' => 'payroll_payable'],
            ['code' => '3000', 'name' => 'Retained Earnings', 'type' => AccountType::Equity, 'key' => 'retained_earnings'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => AccountType::Revenue, 'key' => 'sales_revenue'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => AccountType::CostOfGoodsSold, 'key' => 'cogs'],
            ['code' => '5100', 'name' => 'Inventory Adjustment', 'type' => AccountType::Expense, 'key' => 'inventory_adjustment'],
            ['code' => '6000', 'name' => 'Operating Expense', 'type' => AccountType::Expense, 'key' => 'operating_expense'],
            ['code' => '6100', 'name' => 'Payroll Expense', 'type' => AccountType::Expense, 'key' => 'payroll_expense'],
            ['code' => '6200', 'name' => 'Depreciation Expense', 'type' => AccountType::Expense, 'key' => 'depreciation_expense'],
            ['code' => '7000', 'name' => 'Other Income', 'type' => AccountType::OtherIncome, 'key' => 'other_income'],
            ['code' => '8000', 'name' => 'Other Expense', 'type' => AccountType::OtherExpense, 'key' => 'other_expense'],
            ['code' => '8100', 'name' => 'FX Gain', 'type' => AccountType::OtherIncome, 'key' => 'fx_gain'],
            ['code' => '8200', 'name' => 'FX Loss', 'type' => AccountType::OtherExpense, 'key' => 'fx_loss'],
        ];

        return array_values(array_map(function (array $def) use ($currency) {
            return [
                'code' => $def['code'],
                'name' => $def['name'],
                'parent_id' => null,
                'type' => $def['type'],
                'normal_balance' => $def['normal'] ?? $def['type']->normalBalance(),
                'currency_code' => $currency,
                'is_active' => true,
                'is_system' => true,
                'allow_manual_entry' => ! in_array($def['key'], ['retained_earnings'], true),
                'system_key' => $def['key'],
                'description' => 'System account',
            ];
        }, $defs));
    }
}
