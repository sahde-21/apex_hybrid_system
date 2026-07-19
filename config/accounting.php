<?php

return [

    'base_currency' => env('ACCOUNTING_BASE_CURRENCY', 'IQD'),

    'auto_post' => (bool) env('ACCOUNTING_AUTO_POST', true),

    'cache_ttl' => (int) env('ACCOUNTING_CACHE_TTL', 120),

    /*
    |--------------------------------------------------------------------------
    | System account keys used by posting strategies
    |--------------------------------------------------------------------------
    */
    'system_accounts' => [
        'cash' => 'cash',
        'bank' => 'bank',
        'card_clearing' => 'card_clearing',
        'accounts_receivable' => 'accounts_receivable',
        'inventory' => 'inventory',
        'tax_receivable' => 'tax_receivable',
        'fixed_assets' => 'fixed_assets',
        'accumulated_depreciation' => 'accumulated_depreciation',
        'accounts_payable' => 'accounts_payable',
        'tax_payable' => 'tax_payable',
        'payroll_payable' => 'payroll_payable',
        'retained_earnings' => 'retained_earnings',
        'sales_revenue' => 'sales_revenue',
        'cogs' => 'cogs',
        'inventory_adjustment' => 'inventory_adjustment',
        'operating_expense' => 'operating_expense',
        'payroll_expense' => 'payroll_expense',
        'depreciation_expense' => 'depreciation_expense',
        'other_income' => 'other_income',
        'other_expense' => 'other_expense',
        'fx_gain' => 'fx_gain',
        'fx_loss' => 'fx_loss',
    ],

];
