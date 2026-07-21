<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Performance\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(
        protected HealthCheckService $health,
    ) {}

    public function __invoke(): JsonResponse
    {
        $detailed = (bool) config('performance.health.expose_details', false);
        $result = $this->health->readiness($detailed);
        $healthy = $result['status'] === 'ok';

        $data = [
            'status' => $healthy ? 'ok' : 'degraded',
            'service' => config('app.name'),
            'version' => config('api.version', 'v1'),
            'release' => \App\Support\Release\ReleaseMetadata::public(),
            'checks' => $result['checks'],
            'timestamp' => now()->toIso8601String(),
        ];

        if ($detailed && isset($result['details'])) {
            $data['details'] = $result['details'];
        }

        return ApiResponse::success(
            $data,
            $healthy ? __('scf.performance.health_ok') : __('scf.performance.health_degraded'),
            $healthy ? 200 : 503,
        );
    }
}
