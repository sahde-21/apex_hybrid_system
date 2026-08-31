<?php

namespace App\Support\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestLogContext
{
    protected static ?float $startedAt = null;

    protected static int $queryCount = 0;

    public static function start(): void
    {
        self::$startedAt = microtime(true);
        self::$queryCount = 0;
    }

    public static function incrementQueryCount(): void
    {
        self::$queryCount++;
    }

    /**
     * @return array<string, mixed>
     */
    public static function base(): array
    {
        $request = request();

        return array_filter([
            'request_id' => $request->attributes->get('request_id'),
            'user_id' => auth()->id(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ], fn ($value) => $value !== null && $value !== '');
    }

    public static function logRequestCompleted(int $status): void
    {
        if (! config('performance.instrumentation.log_requests', false)) {
            return;
        }

        $durationMs = self::$startedAt !== null
            ? round((microtime(true) - self::$startedAt) * 1000, 2)
            : null;

        Log::info('request.completed', array_merge(self::base(), [
            'status' => $status,
            'duration_ms' => $durationMs,
            'query_count' => self::$queryCount,
        ]));
    }

    public static function logSlowRequest(int $status, float $durationMs): void
    {
        Log::warning('request.slow', array_merge(self::base(), [
            'status' => $status,
            'duration_ms' => round($durationMs, 2),
            'query_count' => self::$queryCount,
            'threshold_ms' => (int) config('performance.instrumentation.slow_request_ms', 1000),
        ]));
    }
}
