<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Models\PortalCustomer;
use App\Services\Portal\PortalTwoFactorService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        /** @var PortalCustomer|null $customer */
        $customer = PortalCustomer::query()->where('email', $credentials['email'])->first();

        if (! $customer || ! Hash::check($credentials['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        if (! $customer->canAuthenticate()) {
            throw ValidationException::withMessages([
                'email' => __('Your account is inactive.'),
            ]);
        }

        if ($customer->two_factor_enabled) {
            $request->session()->put([
                'portal.two_factor.id' => $customer->id,
                'portal.two_factor.remember' => (bool) ($credentials['remember'] ?? false),
            ]);

            return redirect()->route('portal.two-factor.login');
        }

        return $this->completeLogin($request, $customer, (bool) ($credentials['remember'] ?? false));
    }

    public function showTwoFactorChallenge(): View|RedirectResponse
    {
        if (! session()->has('portal.two_factor.id')) {
            return redirect()->route('portal.login');
        }

        return view('portal.auth.two-factor-challenge');
    }

    public function verifyTwoFactor(Request $request, PortalTwoFactorService $twoFactor): RedirectResponse
    {
        $customerId = $request->session()->get('portal.two_factor.id');

        if (! $customerId) {
            return redirect()->route('portal.login');
        }

        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        /** @var PortalCustomer $customer */
        $customer = PortalCustomer::query()->findOrFail($customerId);
        $code = $request->string('code')->toString() ?: $request->string('recovery_code')->toString();

        if ($code === '' || ! $twoFactor->verifyLoginCode($customer, $code)) {
            throw ValidationException::withMessages([
                'code' => __('The provided two factor authentication code was invalid.'),
            ]);
        }

        $remember = (bool) $request->session()->pull('portal.two_factor.remember', false);
        $request->session()->forget('portal.two_factor.id');

        return $this->completeLogin($request, $customer, $remember);
    }

    protected function completeLogin(Request $request, PortalCustomer $customer, bool $remember = false): RedirectResponse
    {
        Auth::guard('portal')->login($customer, $remember);
        $request->session()->regenerate();

        $customer->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->intended(route('portal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('portal')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function showForgotPassword(): View
    {
        return view('portal.auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker('portal_customers')->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('portal.auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker('portal_customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (PortalCustomer $customer, string $password): void {
                $customer->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($customer));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('portal.login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    public function showVerifyNotice(): View
    {
        return view('portal.auth.verify-email');
    }

    public function sendVerification(Request $request): RedirectResponse
    {
        /** @var PortalCustomer $customer */
        $customer = $request->user('portal');

        if ($customer->hasVerifiedEmail()) {
            return redirect()->route('portal.dashboard');
        }

        $customer->sendEmailVerificationNotification();

        return back()->with('status', __('verification-link-sent'));
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        /** @var PortalCustomer $customer */
        $customer = PortalCustomer::query()->findOrFail($id);

        if (! hash_equals($hash, sha1($customer->getEmailForVerification()))) {
            abort(403);
        }

        if (! $customer->hasVerifiedEmail()) {
            $customer->markEmailAsVerified();
            event(new Verified($customer));
        }

        Auth::guard('portal')->login($customer);

        return redirect()->route('portal.dashboard')->with('status', __('Email verified.'));
    }
}
