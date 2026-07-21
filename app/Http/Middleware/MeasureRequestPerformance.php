<?php

namespace App\Http\Middleware;

use App\Support\Logging\RequestLogContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MeasureRequestPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('performance.instrumentation.enabled', false)) {
            return $next($request);
        }

        RequestLogContext::start();
        $startedAt = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (microtime(true) - $startedAt) * 1000;
        $threshold = (int) config('performance.instrumentation.slow_request_ms', 1000);

        if ($durationMs >= $threshold) {
            RequestLogContext::logSlowRequest($response->getStatusCode(), $durationMs);
        }

        RequestLogContext::logRequestCompleted($response->getStatusCode());

        return $response;
    }
}
