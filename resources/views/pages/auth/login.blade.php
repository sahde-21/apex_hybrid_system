<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-7">
        <div class="text-center lg:text-start">
            <h1 class="text-2xl font-bold tracking-tight text-white">{{ __('Log in to your account') }}</h1>
            <p class="mt-2 text-sm text-slate-400">{{ __('Enter your email and password below to log in') }}</p>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="admin@scf.com"
            />

            <div class="relative space-y-2">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <div class="flex justify-end">
                        <flux:link class="text-sm text-sky-300" :href="route('password.request')" wire:navigate>
                            {{ __('Forgot your password?') }}
                        </flux:link>
                    </div>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Log in') }}
            </flux:button>
        </form>

        <p class="text-center text-xs text-slate-500">
            {{ __('scf.landing.footer_tagline') }}
        </p>
    </div>
</x-layouts::auth>
