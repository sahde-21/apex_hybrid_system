<?php

namespace App\Http\Controllers\Supplier\Auth;

use App\Http\Controllers\Controller;
use App\Models\PortalSupplier;
use App\Services\Supplier\SupplierTwoFactorService;
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

class SupplierAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('supplier.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        /** @var PortalSupplier|null $supplier */
        $supplier = PortalSupplier::query()->where('email', $credentials['email'])->first();

        if (! $supplier || ! Hash::check($credentials['password'], $supplier->password)) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        if (! $supplier->canAuthenticate()) {
            throw ValidationException::withMessages([
                'email' => __('Your account is inactive.'),
            ]);
        }

        if ($supplier->two_factor_enabled) {
            $request->session()->put([
                'supplier.two_factor.id' => $supplier->id,
                'supplier.two_factor.remember' => (bool) ($credentials['remember'] ?? false),
            ]);

            return redirect()->route('supplier.two-factor.login');
        }

        return $this->completeLogin($request, $supplier, (bool) ($credentials['remember'] ?? false));
    }

    public function showTwoFactorChallenge(): View|RedirectResponse
    {
        if (! session()->has('supplier.two_factor.id')) {
            return redirect()->route('supplier.login');
        }

        return view('supplier.auth.two-factor-challenge');
    }

    public function verifyTwoFactor(Request $request, SupplierTwoFactorService $twoFactor): RedirectResponse
    {
        $supplierId = $request->session()->get('supplier.two_factor.id');

        if (! $supplierId) {
            return redirect()->route('supplier.login');
        }

        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        /** @var PortalSupplier $supplier */
        $supplier = PortalSupplier::query()->findOrFail($supplierId);
        $code = $request->string('code')->toString() ?: $request->string('recovery_code')->toString();

        if ($code === '' || ! $twoFactor->verifyLoginCode($supplier, $code)) {
            throw ValidationException::withMessages([
                'code' => __('The provided two factor authentication code was invalid.'),
            ]);
        }

        $remember = (bool) $request->session()->pull('supplier.two_factor.remember', false);
        $request->session()->forget('supplier.two_factor.id');

        return $this->completeLogin($request, $supplier, $remember);
    }

    protected function completeLogin(Request $request, PortalSupplier $supplier, bool $remember = false): RedirectResponse
    {
        Auth::guard('supplier')->login($supplier, $remember);
        $request->session()->regenerate();

        $supplier->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->intended(route('supplier.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('supplier')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('supplier.login');
    }

    public function showForgotPassword(): View
    {
        return view('supplier.auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker('portal_suppliers')->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('supplier.auth.reset-password', [
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

        $status = Password::broker('portal_suppliers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (PortalSupplier $supplier, string $password): void {
                $supplier->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($supplier));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('supplier.login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    public function showVerifyNotice(): View
    {
        return view('supplier.auth.verify-email');
    }

    public function sendVerification(Request $request): RedirectResponse
    {
        /** @var PortalSupplier $supplier */
        $supplier = $request->user('supplier');

        if ($supplier->hasVerifiedEmail()) {
            return redirect()->route('supplier.dashboard');
        }

        $supplier->sendEmailVerificationNotification();

        return back()->with('status', __('verification-link-sent'));
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        /** @var PortalSupplier $supplier */
        $supplier = PortalSupplier::query()->findOrFail($id);

        if (! hash_equals($hash, sha1($supplier->getEmailForVerification()))) {
            abort(403);
        }

        if (! $supplier->hasVerifiedEmail()) {
            $supplier->markEmailAsVerified();
            event(new Verified($supplier));
        }

        Auth::guard('supplier')->login($supplier);

        return redirect()->route('supplier.dashboard')->with('status', __('Email verified.'));
    }
}
