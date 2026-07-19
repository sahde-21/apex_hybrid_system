<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => true,
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = ! in_array(false, $checks, true);

        return ApiResponse::success([
            'status' => $healthy ? 'ok' : 'degraded',
            'service' => config('app.name'),
            'environment' => config('app.env'),
            'version' => config('api.version', 'v1'),
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? __('API is healthy.') : __('API is degraded.'), $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'api-health-'.uniqid('', true);
            Cache::put($key, true, 5);
            $ok = Cache::pull($key) === true;

            return $ok;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkQueue(): bool
    {
        try {
            Queue::size();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
