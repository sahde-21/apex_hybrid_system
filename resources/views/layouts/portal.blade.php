<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}" class="{{ ($appearance ?? 'system') === 'dark' ? 'dark' : '' }}">
    <head>
        @include('partials.head')
        <style>
            [x-cloak] { display: none !important; }
        </style>
        <script>
            (function () {
                const appearance = localStorage.getItem('flux.appearance') || 'system';
                if (appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-slate-50 via-sky-50/40 to-slate-100 antialiased dark:from-zinc-950 dark:via-slate-950 dark:to-zinc-900">
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -start-24 top-0 size-72 rounded-full bg-sky-400/20 blur-3xl dark:bg-sky-500/10"></div>
            <div class="absolute -end-16 bottom-10 size-80 rounded-full bg-cyan-300/20 blur-3xl dark:bg-cyan-400/10"></div>
        </div>

        <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-6 sm:px-6 lg:px-8">
            <header class="portal-glass mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl px-4 py-3 sm:px-5">
                <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-3" wire:navigate>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-sky-600 text-white shadow-md shadow-sky-600/30">
                        <x-app-logo-icon class="size-6 fill-current" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold tracking-tight text-zinc-900 dark:text-white">{{ __('scf.portal.brand') }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('scf.portal.tagline') }}</p>
                    </div>
                </a>

                <nav class="hidden items-center gap-1 md:flex">
                    @foreach ([
                        ['portal.dashboard', 'home', 'scf.portal.dashboard'],
                        ['portal.orders.index', 'shopping-bag', 'scf.portal.orders'],
                        ['portal.invoices.index', 'receipt-percent', 'scf.portal.invoices'],
                        ['portal.tickets.index', 'lifebuoy', 'scf.portal.support'],
                        ['portal.documents.index', 'folder', 'scf.portal.documents'],
                    ] as [$route, $icon, $label])
                        <flux:button
                            :href="route($route)"
                            variant="{{ request()->routeIs(str_replace('.index', '.*', $route)) || request()->routeIs($route) ? 'primary' : 'ghost' }}"
                            size="sm"
                            :icon="$icon"
                            wire:navigate
                        >
                            {{ __($label) }}
                        </flux:button>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    <x-language-switcher />

                    @if (Route::has('portal.notifications.index'))
                        <flux:button :href="route('portal.notifications.index')" variant="ghost" size="sm" icon="bell" wire:navigate />
                    @endif

                    <flux:dropdown position="bottom" align="end">
                        <flux:profile
                            :name="auth('portal')->user()?->name"
                            :initials="auth('portal')->user()?->initials()"
                            icon-trailing="chevron-down"
                        />
                        <flux:menu>
                            <flux:menu.item :href="route('portal.profile.edit')" icon="user" wire:navigate>
                                {{ __('scf.portal.profile') }}
                            </flux:menu.item>
                            <flux:menu.item :href="route('portal.loyalty.index')" icon="gift" wire:navigate>
                                {{ __('scf.portal.loyalty') }}
                            </flux:menu.item>
                            <flux:menu.item :href="route('portal.gift-cards.index')" icon="credit-card" wire:navigate>
                                {{ __('scf.portal.gift_cards') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('portal.logout') }}">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                    {{ __('scf.log_out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </header>

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="mt-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                {{ config('pwa.name', config('app.name')) }} · {{ __('scf.portal.footer') }}
            </footer>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
