<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    */

    'version' => env('API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Default Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => (int) env('API_PER_PAGE', 15),
        'max_per_page' => (int) env('API_MAX_PER_PAGE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        'api' => (int) env('API_RATE_LIMIT', 60),
        'auth' => (int) env('API_AUTH_RATE_LIMIT', 10),
        'write' => (int) env('API_WRITE_RATE_LIMIT', 30),
        'workflow' => (int) env('API_WORKFLOW_RATE_LIMIT', 30),
        'posting' => (int) env('API_POSTING_RATE_LIMIT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    */

    'idempotency' => [
        'ttl_hours' => (int) env('API_IDEMPOTENCY_TTL_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    */

    'audit' => [
        'activity_enabled' => filter_var(env('API_AUDIT_ACTIVITY', true), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Defaults
    |--------------------------------------------------------------------------
    */

    'tokens' => [
        'default_abilities' => ['*'],
        'default_expiration_minutes' => env('API_TOKEN_EXPIRATION_MINUTES'),
        'name_prefix' => env('API_TOKEN_NAME_PREFIX', 'api'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Platforms
    |--------------------------------------------------------------------------
    |
    | Supported client identifiers for mobile, SPA, and integrations.
    |
    */

    'clients' => [
        'mobile',
        'flutter',
        'react',
        'vue',
        'integration',
        'other',
    ],

];
