<?php

namespace App\Http\Middleware;

use App\Models\PortalCustomer;
use App\Models\PortalSupplier;
use App\Models\User;
use App\Services\UserService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()
            ?? $request->user('portal')
            ?? $request->user('supplier');

        if ($user instanceof PortalCustomer) {
            if (! $user->canAuthenticate()) {
                auth('portal')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('portal.login')
                    ->withErrors([
                        'email' => __('Your account is inactive.'),
                    ]);
            }

            return $next($request);
        }

        if ($user instanceof PortalSupplier) {
            if (! $user->canAuthenticate()) {
                auth('supplier')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('supplier.login')
                    ->withErrors([
                        'email' => __('Your account is inactive.'),
                    ]);
            }

            return $next($request);
        }

        if ($user instanceof User && method_exists($user, 'canAuthenticate') && ! $user->canAuthenticate()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => $user->isLocked()
                        ? __('Your account is locked.')
                        : __('Your account is inactive.'),
                ]);
        }

        if ($user instanceof User) {
            if ($user->last_activity_at === null || $user->last_activity_at->lt(now()->subMinute())) {
                app(UserService::class)->touchActivity($user);
            }
        }

        return $next($request);
    }
}
