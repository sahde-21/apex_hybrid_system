<?php

namespace App\Enums;

enum FinancialReportType: string
{
    case ProfitLoss = 'profit_loss';
    case BalanceSheet = 'balance_sheet';
    case CashFlow = 'cash_flow';
    case TrialBalance = 'trial_balance';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::ProfitLoss => __('Profit & Loss'),
            self::BalanceSheet => __('Balance Sheet'),
            self::CashFlow => __('Cash Flow'),
            self::TrialBalance => __('Trial Balance'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ProfitLoss => 'blue',
            self::BalanceSheet => 'purple',
            self::CashFlow => 'green',
            self::TrialBalance => 'zinc',
        };
    }
}
