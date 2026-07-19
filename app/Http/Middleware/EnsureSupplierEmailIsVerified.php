<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupplierEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('supplier');

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect()->route('supplier.verification.notice');
        }

        return $next($request);
    }
}
