<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('portal');

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? abort(403, __('Your email address is not verified.'))
                : redirect()->route('portal.verification.notice');
        }

        return $next($request);
    }
}
