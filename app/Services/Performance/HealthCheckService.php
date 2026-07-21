<?php

namespace App\Services\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthCheckService
{
    /**
     * @return array{status: string, checks: array<string, bool>}
     */
    public function liveness(): array
    {
        return [
            'status' => 'ok',
            'checks' => [
                'app' => true,
            ],
        ];
    }

    /**
     * @return array{status: string, checks: array<string, bool>, details?: array<string, mixed>}
     */
    public function readiness(bool $detailed = false): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];

        $healthy = ! in_array(false, $checks, true);

        $payload = [
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ];

        if ($detailed) {
            $payload['details'] = [
                'environment' => config('app.env'),
                'cache_driver' => config('cache.default'),
                'queue_driver' => config('queue.default'),
                'database_driver' => config('database.default'),
            ];
        }

        return $payload;
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return Schema::hasTable('users');
        } catch (Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'health:'.uniqid('', true);
            Cache::put($key, true, 5);

            return Cache::pull($key) === true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkQueue(): bool
    {
        try {
            Queue::connection();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            $path = storage_path('framework/cache');
            File::ensureDirectoryExists($path);

            return is_writable($path);
        } catch (Throwable) {
            return false;
        }
    }
}
