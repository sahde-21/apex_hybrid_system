<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}" class="dark" data-theme="auth">
    <head>
        @include('partials.head')
        <style>
            .scf-auth-bg {
                background:
                    radial-gradient(ellipse 80% 50% at 20% 20%, rgba(37, 99, 235, 0.45), transparent 55%),
                    radial-gradient(ellipse 60% 40% at 85% 70%, rgba(56, 189, 248, 0.2), transparent 50%),
                    linear-gradient(160deg, #020617 0%, #0a1628 50%, #020617 100%);
            }
            .scf-auth-glass {
                background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.03));
                border: 1px solid rgba(148, 163, 184, 0.2);
                backdrop-filter: blur(22px);
                box-shadow: 0 30px 80px rgba(2, 6, 23, 0.55);
            }
        </style>
    </head>
    <body class="scf-auth-bg min-h-screen antialiased text-slate-100">
        <div class="absolute inset-x-0 top-0 z-30 flex items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold text-white" wire:navigate>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10">
                    <x-app-logo-icon class="h-6 fill-current text-sky-300" />
                </span>
                <span class="hidden sm:inline">{{ config('app.name', 'SCF') }}</span>
            </a>
            <div class="flex items-center gap-3">
                <x-language-switcher />
            </div>
        </div>

        <div class="relative grid min-h-screen lg:grid-cols-2">
            <div class="relative hidden flex-col justify-between p-10 lg:flex xl:p-14">
                <div class="mt-16 max-w-md">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-400">{{ __('scf.landing.brand_short') }}</p>
                    <h1 class="mt-4 text-4xl font-bold leading-tight text-white xl:text-5xl">{{ __('scf.landing.hero_title') }}</h1>
                    <p class="mt-4 text-base leading-relaxed text-slate-400">{{ __('scf.landing.hero_subtitle') }}</p>
                </div>

                <div class="grid max-w-lg grid-cols-2 gap-4">
                    <div class="scf-auth-glass rounded-2xl p-4">
                        <p class="text-2xl font-bold text-white">50+</p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('scf.landing.stats_modules') }}</p>
                    </div>
                    <div class="scf-auth-glass rounded-2xl p-4">
                        <p class="text-2xl font-bold text-white">3</p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('scf.landing.stats_locales') }}</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center px-4 py-24 sm:px-8">
                <div class="scf-auth-glass w-full max-w-md rounded-3xl p-7 sm:p-9">
                    <div class="mb-8 flex flex-col items-center gap-3 lg:hidden">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10">
                            <x-app-logo-icon class="h-7 fill-current text-sky-300" />
                        </span>
                        <p class="text-lg font-semibold text-white">{{ config('app.name', 'SCF') }}</p>
                    </div>
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
