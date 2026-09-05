<?php

use App\Support\Security\CorsAllowedOrigins;

$appEnv = env('APP_ENV', 'production');
$appEnv = is_string($appEnv) && $appEnv !== '' ? $appEnv : 'production';

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | This determines what cross-origin operations may execute in web browsers.
    |
    | SCF_CORS_ALLOWED_ORIGINS:
    | - Comma-separated list of allowed origins (no trailing spaces required).
    | - Local/testing: unset defaults to "*" for developer convenience.
    | - Production: unset defaults to [] (deny browser cross-origin until set).
    | - Production: an explicit "*" entry is always stripped (never allowed).
    |
    | Example:
    | SCF_CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => CorsAllowedOrigins::fromEnv(
        env('SCF_CORS_ALLOWED_ORIGINS'),
        $appEnv,
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
