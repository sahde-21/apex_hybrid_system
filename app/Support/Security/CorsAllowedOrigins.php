<?php

namespace App\Support\Security;

/**
 * Resolve browser CORS allowed origins from environment.
 *
 * Production never allows a wildcard. Local/testing default to "*" when unset
 * so API feature tests and local SPA work without extra configuration.
 */
final class CorsAllowedOrigins
{
    /**
     * @param  mixed  $raw  Value from env(); non-strings are treated as unset.
     * @return list<string>
     */
    public static function fromEnv(mixed $raw, string $appEnv): array
    {
        $rawString = is_string($raw) ? $raw : null;
        $env = strtolower(trim($appEnv));
        $isProduction = $env === 'production';

        if ($rawString === null || trim($rawString) === '') {
            return $isProduction ? [] : ['*'];
        }

        $origins = array_values(array_filter(
            array_map(
                static fn (string $origin): string => trim($origin),
                explode(',', $rawString),
            ),
            static fn (string $origin): bool => $origin !== '',
        ));

        if ($isProduction) {
            return array_values(array_filter(
                $origins,
                static fn (string $origin): bool => $origin !== '*',
            ));
        }

        return $origins;
    }
}
