<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    */

    'headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),

        'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN'),
        'x_content_type_options' => 'nosniff',
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env(
            'SECURITY_PERMISSIONS_POLICY',
            'camera=(), microphone=(), geolocation=(), payment=()'
        ),
        'cross_origin_opener_policy' => env('SECURITY_COOP', 'same-origin'),
        'cross_origin_resource_policy' => env('SECURITY_CORP', 'same-site'),
        'content_security_policy_report_only' => env('SECURITY_CSP_REPORT_ONLY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Privileged Roles
    |--------------------------------------------------------------------------
    |
    | Roles that can only be assigned by a super-admin.
    |
    */

    'privileged_roles' => [
        'super-admin',
        'owner',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Audit Attributes
    |--------------------------------------------------------------------------
    */

    'audit_redact' => [
        'password',
        'password_confirmation',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'token',
        'plain_text_token',
        'api_token',
        'secret',
    ],

];
