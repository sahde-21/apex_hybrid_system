<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiTokenAbility
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if ($request->bearerToken() === null) {
            return $next($request);
        }

        $token = $user->currentAccessToken();

        if ($token->can('*')) {
            return $next($request);
        }

        foreach ($abilities as $ability) {
            if ($token->can($ability)) {
                return $next($request);
            }
        }

        abort(403, __('scf.api.insufficient_token_ability'));
    }
}
