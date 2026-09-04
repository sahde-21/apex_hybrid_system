<?php

use App\Support\Security\CorsAllowedOrigins;

test('local and testing default to wildcard when cors origins unset', function () {
    expect(CorsAllowedOrigins::fromEnv(null, 'local'))->toBe(['*'])
        ->and(CorsAllowedOrigins::fromEnv('', 'testing'))->toBe(['*'])
        ->and(CorsAllowedOrigins::fromEnv('   ', 'local'))->toBe(['*']);
});

test('production defaults to empty origins when cors origins unset', function () {
    expect(CorsAllowedOrigins::fromEnv(null, 'production'))->toBe([])
        ->and(CorsAllowedOrigins::fromEnv('', 'production'))->toBe([]);
});

test('comma separated cors origins are parsed and trimmed', function () {
    expect(CorsAllowedOrigins::fromEnv(
        'https://app.example.com, https://admin.example.com ,',
        'production',
    ))->toBe([
        'https://app.example.com',
        'https://admin.example.com',
    ]);
});

test('production strips wildcard even when explicitly configured', function () {
    expect(CorsAllowedOrigins::fromEnv('*', 'production'))->toBe([])
        ->and(CorsAllowedOrigins::fromEnv(
            'https://app.example.com,*,https://admin.example.com',
            'production',
        ))->toBe([
            'https://app.example.com',
            'https://admin.example.com',
        ]);
});

test('non production may keep an explicit wildcard', function () {
    expect(CorsAllowedOrigins::fromEnv('*', 'local'))->toBe(['*']);
});

test('application cors config loads without unrestricted production wildcard', function () {
    $origins = config('cors.allowed_origins');

    expect($origins)->toBeArray();

    if (app()->environment('production')) {
        expect($origins)->not->toContain('*');
    }
});
