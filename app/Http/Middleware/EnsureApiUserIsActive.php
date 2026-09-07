<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\UserService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if (! $user->canAuthenticate()) {
            if ($request->bearerToken() !== null) {
                $user->currentAccessToken()->delete();
            }

            return ApiResponse::error(
                message: $user->isLocked()
                    ? __('Your account is locked.')
                    : __('Your account is inactive.'),
                status: 403,
            );
        }

        if ($user->last_activity_at === null || $user->last_activity_at->lt(now()->subMinute())) {
            app(UserService::class)->touchActivity($user);
        }

        return $next($request);
    }
}
