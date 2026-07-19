<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
    <head>
        @include('partials.head')
        <script>
            (function () {
                const appearance = localStorage.getItem('flux.appearance') || 'system';
                if (appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-slate-50 via-sky-50/50 to-cyan-50 antialiased dark:from-zinc-950 dark:via-slate-950 dark:to-zinc-900">
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -start-20 top-10 size-72 rounded-full bg-sky-400/25 blur-3xl dark:bg-sky-500/10"></div>
            <div class="absolute -end-10 bottom-0 size-96 rounded-full bg-cyan-300/20 blur-3xl dark:bg-cyan-500/10"></div>
        </div>

        <div class="relative flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2" wire:navigate>
                <span class="flex size-12 items-center justify-center rounded-2xl bg-sky-600 text-white shadow-lg shadow-sky-600/30">
                    <x-app-logo-icon class="size-7 fill-current" />
                </span>
                <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('scf.portal.brand') }}</span>
            </a>

            <div class="portal-glass w-full max-w-md rounded-2xl p-6 sm:p-8">
                {{ $slot }}
            </div>

            <x-language-switcher />
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
