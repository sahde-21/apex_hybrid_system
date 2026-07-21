<?php

return [

    'enabled' => (bool) env('INTELLIGENCE_ENABLED', true),

    'assistant_enabled' => (bool) env('INTELLIGENCE_ASSISTANT_ENABLED', true),

    'forecasting_enabled' => (bool) env('INTELLIGENCE_FORECASTING_ENABLED', true),

    'default_date_range_days' => (int) env('INTELLIGENCE_DEFAULT_RANGE_DAYS', 30),

    'max_date_range_days' => (int) env('INTELLIGENCE_MAX_RANGE_DAYS', 366),

    'cache_ttl' => (int) env('INTELLIGENCE_CACHE_TTL', 300),

    'cache_prefix' => env('INTELLIGENCE_CACHE_PREFIX', 'scf:intelligence:'),

    'forecast_horizon_periods' => (int) env('INTELLIGENCE_FORECAST_HORIZON', 3),

    'min_historical_points' => (int) env('INTELLIGENCE_MIN_HISTORY', 3),

    'moving_average_window' => (int) env('INTELLIGENCE_MA_WINDOW', 3),

    'trend_sensitivity' => (float) env('INTELLIGENCE_TREND_SENSITIVITY', 0.05),

    'anomaly_z_threshold' => (float) env('INTELLIGENCE_ANOMALY_Z', 2.0),

    'max_export_rows' => (int) env('INTELLIGENCE_MAX_EXPORT_ROWS', 5000),

    'snapshot_retention_days' => (int) env('INTELLIGENCE_SNAPSHOT_RETENTION_DAYS', 30),

    'queue' => env('INTELLIGENCE_QUEUE', 'default'),

    'health_score_weights' => [
        'financial' => 20,
        'sales' => 15,
        'cash_collection' => 15,
        'purchasing' => 10,
        'inventory' => 10,
        'customers' => 10,
        'suppliers' => 10,
        'operations' => 5,
        'system' => 5,
    ],

    'alert_thresholds' => [
        'overdue_invoice_amount' => (float) env('INTELLIGENCE_OVERDUE_INVOICE_THRESHOLD', 1000),
        'low_stock_count' => (int) env('INTELLIGENCE_LOW_STOCK_THRESHOLD', 5),
        'queue_pending_warning' => (int) env('INTELLIGENCE_QUEUE_PENDING_WARNING', 100),
        'large_invoice_multiplier' => (float) env('INTELLIGENCE_LARGE_INVOICE_MULTIPLIER', 3.0),
    ],

    'modules' => [
        'executive' => true,
        'financial' => true,
        'sales' => true,
        'purchasing' => true,
        'inventory' => true,
        'customers' => true,
        'suppliers' => true,
        'operations' => true,
        'forecasts' => true,
        'alerts' => true,
        'recommendations' => true,
        'assistant' => true,
    ],

    'permissions' => [
        'view' => 'intelligence.view',
        'executive' => 'intelligence.executive.view',
        'financial' => 'intelligence.financial.view',
        'sales' => 'intelligence.sales.view',
        'purchasing' => 'intelligence.purchasing.view',
        'inventory' => 'intelligence.inventory.view',
        'customers' => 'intelligence.customers.view',
        'suppliers' => 'intelligence.suppliers.view',
        'operations' => 'intelligence.operations.view',
        'forecasts' => 'intelligence.forecasts.view',
        'alerts_view' => 'intelligence.alerts.view',
        'alerts_manage' => 'intelligence.alerts.manage',
        'recommendations_view' => 'intelligence.recommendations.view',
        'recommendations_manage' => 'intelligence.recommendations.manage',
        'assistant' => 'intelligence.assistant.use',
        'export' => 'intelligence.export',
        'configure' => 'intelligence.configure',
    ],

];
