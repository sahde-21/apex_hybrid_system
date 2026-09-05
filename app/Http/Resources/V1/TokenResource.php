<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @mixin PersonalAccessToken
 */
class TokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $currentToken = (is_object($user) && method_exists($user, 'currentAccessToken'))
            ? $user->currentAccessToken()
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'abilities' => $this->abilities ?? [],
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'is_current' => $currentToken?->id === $this->id,
        ];
    }
}
