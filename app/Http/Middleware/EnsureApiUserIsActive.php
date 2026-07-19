<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Services\UserService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (method_exists($user, 'canAuthenticate') && ! $user->canAuthenticate()) {
            if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
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
