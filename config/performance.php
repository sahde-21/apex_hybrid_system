<?php

return [

    'cache' => [
        'prefix' => env('PERFORMANCE_CACHE_PREFIX', 'scf:perf:'),
        'dashboard_ttl' => (int) env('PERFORMANCE_DASHBOARD_CACHE_TTL', 120),
        'reference_ttl' => (int) env('PERFORMANCE_REFERENCE_CACHE_TTL', 3600),
        'navigation_ttl' => (int) env('PERFORMANCE_NAVIGATION_CACHE_TTL', 600),
    ],

    'pagination' => [
        'default' => (int) env('PERFORMANCE_DEFAULT_PER_PAGE', 15),
        'max' => (int) env('PERFORMANCE_MAX_PER_PAGE', 100),
        'activity' => (int) env('PERFORMANCE_ACTIVITY_PER_PAGE', 20),
        'audit' => (int) env('PERFORMANCE_AUDIT_PER_PAGE', 20),
    ],

    'search' => [
        'min_length' => (int) env('PERFORMANCE_SEARCH_MIN_LENGTH', 2),
        'max_results_per_module' => (int) env('PERFORMANCE_SEARCH_MAX_PER_MODULE', 5),
        'max_total_results' => (int) env('PERFORMANCE_SEARCH_MAX_TOTAL', 25),
    ],

    'instrumentation' => [
        'enabled' => env('PERFORMANCE_INSTRUMENTATION', false),
        'log_requests' => env('PERFORMANCE_LOG_REQUESTS', false),
        'slow_request_ms' => (int) env('PERFORMANCE_SLOW_REQUEST_MS', 1000),
    ],

    'database' => [
        'slow_query_ms' => (int) env('PERFORMANCE_SLOW_QUERY_MS', 500),
        'log_slow_queries' => env('PERFORMANCE_LOG_SLOW_QUERIES', false),
        'log_query_count' => env('PERFORMANCE_LOG_QUERY_COUNT', false),
    ],

    'queue' => [
        'notifications' => env('PERFORMANCE_QUEUE_NOTIFICATIONS', true),
        'exports' => env('PERFORMANCE_QUEUE_EXPORTS', true),
        'retries' => (int) env('PERFORMANCE_QUEUE_RETRIES', 3),
        'timeout' => (int) env('PERFORMANCE_QUEUE_TIMEOUT', 120),
    ],

    'backup' => [
        'retention_days' => (int) env('PERFORMANCE_BACKUP_RETENTION_DAYS', 14),
        'directory' => env('PERFORMANCE_BACKUP_DIRECTORY'),
    ],

    'health' => [
        'expose_details' => env('PERFORMANCE_HEALTH_EXPOSE_DETAILS', false),
    ],

    'idempotency' => [
        'prune_after_hours' => (int) env('PERFORMANCE_IDEMPOTENCY_PRUNE_HOURS', 48),
    ],

    'scheduler' => [
        'overdue_documents' => env('PERFORMANCE_SCHEDULE_OVERDUE', true),
        'expire_documents' => env('PERFORMANCE_SCHEDULE_EXPIRE', true),
        'prune_idempotency' => env('PERFORMANCE_SCHEDULE_PRUNE_IDEMPOTENCY', true),
        'prune_backups' => env('PERFORMANCE_SCHEDULE_PRUNE_BACKUPS', true),
        'warm_cache' => env('PERFORMANCE_SCHEDULE_WARM_CACHE', true),
    ],

];
