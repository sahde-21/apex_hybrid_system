<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UsePortalGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('portal');

        return $next($request);
    }
}
