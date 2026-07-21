<?php

namespace App\Support\Performance;

use App\Models\Currency;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PerformanceCache
{
    public static function prefix(): string
    {
        return (string) config('performance.cache.prefix', 'scf:perf:');
    }

    public static function key(string ...$parts): string
    {
        return self::prefix().implode(':', array_filter($parts));
    }

    public static function dashboardKey(User $user): string
    {
        return self::key('dashboard', (string) $user->id, app()->getLocale());
    }

    public static function forgetDashboard(?User $user = null): void
    {
        if ($user !== null) {
            Cache::forget(self::dashboardKey($user));

            return;
        }

        // Best-effort: file/database cache cannot wildcard-forget; rely on TTL.
    }

    public static function forgetReferenceData(): void
    {
        Cache::forget(self::key('currencies'));
        Cache::forget(self::key('tax-rates'));
    }

    /**
     * @return list<array{id: int, code: string, name: string}>
     */
    public static function currencies(): array
    {
        $ttl = (int) config('performance.cache.reference_ttl', 3600);

        return Cache::remember(self::key('currencies'), $ttl, function () {
            if (! class_exists(Currency::class)) {
                return [];
            }

            return Currency::query()
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (Currency $currency) => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                ])
                ->all();
        });
    }

    /**
     * @return list<array{id: int, name: string, rate: string}>
     */
    public static function taxRates(): array
    {
        $ttl = (int) config('performance.cache.reference_ttl', 3600);

        return Cache::remember(self::key('tax-rates'), $ttl, function () {
            if (! class_exists(TaxRate::class)) {
                return [];
            }

            return TaxRate::query()
                ->orderBy('name')
                ->get(['id', 'name', 'rate'])
                ->map(fn (TaxRate $rate) => [
                    'id' => $rate->id,
                    'name' => $rate->name,
                    'rate' => (string) $rate->rate,
                ])
                ->all();
        });
    }
}
