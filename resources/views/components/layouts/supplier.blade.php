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
    <body class="min-h-screen bg-gradient-to-br from-emerald-50 via-teal-50/40 to-slate-100 antialiased dark:from-zinc-950 dark:via-emerald-950/40 dark:to-zinc-900">
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -start-24 top-0 size-72 rounded-full bg-emerald-400/20 blur-3xl dark:bg-emerald-500/10"></div>
            <div class="absolute -end-16 bottom-10 size-80 rounded-full bg-teal-300/20 blur-3xl dark:bg-teal-400/10"></div>
        </div>

        <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-6 sm:px-6 lg:px-8">
            <header class="portal-glass mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl px-4 py-3 sm:px-5">
                <a href="{{ route('supplier.dashboard') }}" class="flex items-center gap-3" wire:navigate>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-600/30">
                        <x-app-logo-icon class="size-6 fill-current" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold tracking-tight text-zinc-900 dark:text-white">{{ __('scf.supplier_portal.brand') }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('scf.supplier_portal.tagline') }}</p>
                    </div>
                </a>

                <nav class="hidden items-center gap-1 lg:flex">
                    @foreach ([
                        ['supplier.dashboard', 'home', 'scf.supplier_portal.dashboard'],
                        ['supplier.purchase-orders.index', 'clipboard-document-list', 'scf.supplier_portal.purchase_orders'],
                        ['supplier.bills.index', 'receipt-percent', 'scf.supplier_portal.bills'],
                        ['supplier.deliveries.index', 'truck', 'scf.supplier_portal.deliveries'],
                        ['supplier.documents.index', 'folder', 'scf.supplier_portal.documents'],
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

                    <flux:button :href="route('supplier.notifications.index')" variant="ghost" size="sm" icon="bell" wire:navigate />

                    <flux:dropdown position="bottom" align="end">
                        <flux:profile
                            :name="auth('supplier')->user()?->name"
                            :initials="auth('supplier')->user()?->initials()"
                            icon-trailing="chevron-down"
                        />
                        <flux:menu>
                            <flux:menu.item :href="route('supplier.profile.edit')" icon="user" wire:navigate>
                                {{ __('scf.supplier_portal.profile') }}
                            </flux:menu.item>
                            <flux:menu.item :href="route('supplier.payments.index')" icon="banknotes" wire:navigate>
                                {{ __('scf.supplier_portal.payments') }}
                            </flux:menu.item>
                            <flux:menu.item :href="route('supplier.contracts.index')" icon="document-text" wire:navigate>
                                {{ __('scf.supplier_portal.contracts') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('supplier.logout') }}">
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
                {{ config('pwa.name', config('app.name')) }} · {{ __('scf.supplier_portal.footer') }}
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
