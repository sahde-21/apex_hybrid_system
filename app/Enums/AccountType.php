<?php

namespace App\Enums;

enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case CostOfGoodsSold = 'cogs';
    case Expense = 'expense';
    case OtherIncome = 'other_income';
    case OtherExpense = 'other_expense';

    public function label(): string
    {
        return match ($this) {
            self::Asset => __('scf.accounting_engine.account_type_asset'),
            self::Liability => __('scf.accounting_engine.account_type_liability'),
            self::Equity => __('scf.accounting_engine.account_type_equity'),
            self::Revenue => __('scf.accounting_engine.account_type_revenue'),
            self::CostOfGoodsSold => __('scf.accounting_engine.account_type_cogs'),
            self::Expense => __('scf.accounting_engine.account_type_expense'),
            self::OtherIncome => __('scf.accounting_engine.account_type_other_income'),
            self::OtherExpense => __('scf.accounting_engine.account_type_other_expense'),
        };
    }

    public function normalBalance(): NormalBalance
    {
        return match ($this) {
            self::Asset, self::CostOfGoodsSold, self::Expense, self::OtherExpense => NormalBalance::Debit,
            self::Liability, self::Equity, self::Revenue, self::OtherIncome => NormalBalance::Credit,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
