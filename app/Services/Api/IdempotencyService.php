<?php

namespace App\Services\Api;

use App\Models\ApiIdempotencyKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class IdempotencyService
{
    public function fingerprint(Request $request): string
    {
        $payload = json_encode([
            'method' => strtoupper($request->method()),
            'path' => $request->path(),
            'body' => $request->all(),
        ]);

        return hash('sha256', (string) $payload);
    }

    public function findValid(int $userId, string $key): ?ApiIdempotencyKey
    {
        return ApiIdempotencyKey::query()
            ->where('user_id', $userId)
            ->where('key', $key)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * @param  array<string, mixed>  $responseBody
     */
    public function store(
        int $userId,
        ?int $tokenId,
        string $key,
        string $fingerprint,
        string $method,
        string $path,
        int $responseStatus,
        array $responseBody,
    ): ApiIdempotencyKey {
        $ttlHours = (int) config('api.idempotency.ttl_hours', 24);

        return ApiIdempotencyKey::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'key' => $key,
            ],
            [
                'token_id' => $tokenId,
                'fingerprint' => $fingerprint,
                'method' => strtoupper($method),
                'path' => $path,
                'response_status' => $responseStatus,
                'response_body' => $responseBody,
                'expires_at' => now()->addHours($ttlHours),
            ],
        );
    }

    public function purgeExpired(): int
    {
        return ApiIdempotencyKey::query()
            ->where('expires_at', '<=', now())
            ->delete();
    }
}
