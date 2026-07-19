<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UseSupplierGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('supplier');

        return $next($request);
    }
}
