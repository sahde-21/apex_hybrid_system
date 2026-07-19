<?php

return [

    /*
    |--------------------------------------------------------------------------
    | BI cache
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => (int) env('BI_CACHE_TTL', 300),

    'cache_prefix' => 'scf:bi:',

    /*
    |--------------------------------------------------------------------------
    | Dashboard packs (role-oriented executive views)
    |--------------------------------------------------------------------------
    */
    'dashboards' => [
        'ceo' => [
            'permission' => 'analytics.read',
            'kpis' => ['revenue', 'gross_profit', 'net_profit', 'cash_flow', 'outstanding_invoices', 'inventory_value', 'sales_month', 'expenses'],
            'charts' => ['revenue_trend', 'profit_mix', 'top_products', 'cash_flow', 'forecast'],
        ],
        'owner' => [
            'permission' => 'analytics.read',
            'kpis' => ['revenue', 'net_profit', 'sales_today', 'sales_week', 'sales_month', 'expenses', 'cash_flow', 'customers'],
            'charts' => ['revenue_trend', 'branch_compare', 'top_customers', 'expenses_trend', 'forecast'],
        ],
        'branch' => [
            'permission' => 'analytics.read',
            'kpis' => ['sales_today', 'sales_week', 'sales_month', 'revenue', 'outstanding_invoices', 'low_stock'],
            'charts' => ['revenue_trend', 'branch_compare', 'top_products', 'monthly_compare'],
        ],
        'warehouse' => [
            'permission' => 'analytics.read',
            'kpis' => ['inventory_value', 'low_stock', 'sales_month'],
            'charts' => ['inventory_value', 'low_stock', 'top_products'],
        ],
        'finance' => [
            'permission' => 'analytics.read',
            'kpis' => ['revenue', 'gross_profit', 'net_profit', 'expenses', 'cash_flow', 'outstanding_invoices', 'outstanding_bills', 'payroll_cost'],
            'charts' => ['revenue_trend', 'cash_flow', 'profit_mix', 'expenses_trend', 'forecast'],
        ],
        'hr' => [
            'permission' => 'analytics.read',
            'kpis' => ['payroll_cost', 'employees'],
            'charts' => ['payroll_cost', 'attendance_heatmap'],
        ],
        'sales' => [
            'permission' => 'analytics.read',
            'kpis' => ['sales_today', 'sales_week', 'sales_month', 'revenue', 'outstanding_invoices', 'customers'],
            'charts' => ['revenue_trend', 'top_products', 'top_customers', 'monthly_compare', 'forecast'],
        ],
        'crm' => [
            'permission' => 'analytics.read',
            'kpis' => ['open_leads', 'open_tickets', 'customers', 'sales_month'],
            'charts' => ['leads_funnel', 'top_customers'],
        ],
        'manufacturing' => [
            'permission' => 'analytics.read',
            'kpis' => ['production_orders', 'inventory_value', 'low_stock'],
            'charts' => ['production_trend', 'quality_mix'],
        ],
    ],

    'reports' => [
        'executive' => 'analytics.read',
        'financial' => 'financial-reports.read',
        'inventory' => 'products.read',
        'sales' => 'sale-orders.read',
        'purchase' => 'purchase-orders.read',
        'manufacturing' => 'production-orders.read',
        'hr' => 'employees.read',
        'crm' => 'leads.read',
        'project' => 'project-tasks.read',
        'branch' => 'branches.read',
    ],
];
