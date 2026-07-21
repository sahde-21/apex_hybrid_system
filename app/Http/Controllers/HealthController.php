<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Services\Performance\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(
        protected HealthCheckService $health,
    ) {}

    public function live(): JsonResponse
    {
        $result = $this->health->liveness();

        return response()->json([
            'status' => $result['status'],
        ], 200);
    }

    public function ready(): JsonResponse
    {
        $detailed = (bool) config('performance.health.expose_details', false);
        $result = $this->health->readiness($detailed);
        $status = $result['status'] === 'ok' ? 200 : 503;

        $payload = [
            'status' => $result['status'],
            'checks' => $result['checks'],
        ];

        if ($detailed && isset($result['details'])) {
            $payload['details'] = $result['details'];
        }

        return response()->json($payload, $status);
    }
}
