<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\IdempotencyConflictException;
use App\Services\Api\IdempotencyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleIdempotency
{
    public function __construct(
        protected IdempotencyService $idempotency,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! is_string($key) || trim($key) === '') {
            return $next($request);
        }

        $key = trim($key);
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $fingerprint = $this->idempotency->fingerprint($request);
        $existing = $this->idempotency->findValid($user->id, $key);

        if ($existing !== null) {
            if ($existing->fingerprint !== $fingerprint) {
                throw new IdempotencyConflictException(__('scf.api.idempotency_payload_mismatch'));
            }

            if ($existing->response_body !== null && $existing->response_status !== null) {
                return response()->json(
                    $existing->response_body,
                    $existing->response_status,
                    $this->responseHeaders($request),
                );
            }
        }

        /** @var Response $response */
        $response = $next($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $content = json_decode($response->getContent(), true);

            if (is_array($content)) {
                $this->idempotency->store(
                    userId: $user->id,
                    tokenId: $user->currentAccessToken()?->id,
                    key: $key,
                    fingerprint: $fingerprint,
                    method: $request->method(),
                    path: $request->path(),
                    responseStatus: $response->getStatusCode(),
                    responseBody: $content,
                );
            }
        }

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function responseHeaders(Request $request): array
    {
        $headers = [];

        if ($requestId = $request->attributes->get('request_id')) {
            $headers['X-Request-Id'] = (string) $requestId;
        }

        $headers['X-Api-Version'] = (string) config('api.version', 'v1');

        return $headers;
    }
}
