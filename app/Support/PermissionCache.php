<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Detects and repairs a stale Spatie permission cache that reports zero
 * permissions while the database still contains permission records.
 *
 * That failure mode causes PermissionDoesNotExist during sync and makes
 * Gate authorization fall through to deny for non–super-admin users.
 */
class PermissionCache
{
    public static function healIfStale(): void
    {
        try {
            /** @var PermissionRegistrar $registrar */
            $registrar = app(PermissionRegistrar::class);
            $cache = $registrar->getCacheRepository()->get(config('permission.cache.key'));

            if (! is_array($cache) || ! array_key_exists('permissions', $cache)) {
                return;
            }

            $cachedCount = is_countable($cache['permissions']) ? count($cache['permissions']) : 0;

            if ($cachedCount > 0) {
                return;
            }

            if (! Permission::query()->exists()) {
                return;
            }

            $registrar->forgetCachedPermissions();
        } catch (Throwable) {
            // Never block the request pipeline for cache hygiene.
        }
    }
}
