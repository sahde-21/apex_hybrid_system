<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('These credentials do not match our records.')],
            ]);
        }

        if (! $user->canAuthenticate()) {
            return ApiResponse::error(
                message: $user->isLocked()
                    ? __('Your account is locked.')
                    : __('Your account is inactive.'),
                status: 403,
            );
        }

        $abilities = $request->input('abilities', config('api.tokens.default_abilities', ['*']));
        $deviceName = $this->resolveDeviceName($request);
        $expiresAt = $this->resolveExpiration($request);

        $token = $user->createToken($deviceName, $abilities, $expiresAt);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_activity_at' => now(),
        ])->save();

        $user->loadMissing(['roles', 'permissions']);

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'abilities' => $abilities,
            'user' => new UserResource($user),
        ], __('Authenticated successfully.'), 201);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing(['roles', 'permissions']);

        return ApiResponse::success(
            new UserResource($user),
            __('Authenticated user retrieved.'),
        );
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        if ($currentToken !== null) {
            $currentToken->delete();
        }

        return ApiResponse::success(null, __('Logged out successfully.'));
    }

    public function logoutAll(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->tokens()->delete();

        return ApiResponse::success(null, __('Logged out from all devices successfully.'));
    }

    private function resolveDeviceName(LoginRequest $request): string
    {
        $prefix = (string) config('api.tokens.name_prefix', 'api');
        $client = $request->input('client', 'other');
        $deviceName = $request->input('device_name');

        if (is_string($deviceName) && $deviceName !== '') {
            return $deviceName;
        }

        return sprintf('%s:%s', $prefix, $client);
    }

    private function resolveExpiration(LoginRequest $request): ?\DateTimeInterface
    {
        if ($request->filled('expires_at')) {
            return $request->date('expires_at');
        }

        $minutes = config('api.tokens.default_expiration_minutes');

        if ($minutes === null || $minutes === '') {
            return null;
        }

        return now()->addMinutes((int) $minutes);
    }
}
