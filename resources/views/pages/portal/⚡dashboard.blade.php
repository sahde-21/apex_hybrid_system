<?php

use App\Services\Portal\PortalDashboardService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.portal')] #[Title('Portal Dashboard')] class extends Component {
    #[Computed]
    public function metrics(): array
    {
        return app(PortalDashboardService::class)->metrics(auth('portal')->user());
    }

    #[Computed]
    public function recentActivity()
    {
        return app(PortalDashboardService::class)->recentActivity(auth('portal')->user());
    }
}; ?>

@php
    $m = $this->metrics;
    $kpis = [
        ['key' => 'orders', 'value' => $m['orders'], 'icon' => 'shopping-bag', 'tone' => 'sky'],
        ['key' => 'invoices', 'value' => $m['invoices'], 'icon' => 'receipt-percent', 'tone' => 'indigo'],
        ['key' => 'payments', 'value' => $m['payments'], 'icon' => 'banknotes', 'tone' => 'emerald'],
        ['key' => 'outstanding', 'value' => $m['outstanding'], 'icon' => 'scale', 'tone' => 'amber', 'money' => true],
        ['key' => 'loyalty_points', 'value' => $m['loyalty_points'], 'icon' => 'gift', 'tone' => 'violet'],
        ['key' => 'open_tickets', 'value' => $m['open_tickets'], 'icon' => 'lifebuoy', 'tone' => 'rose'],
    ];
@endphp

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-6 sm:p-8">
        <p class="text-sm font-medium text-sky-700 dark:text-sky-300">{{ __('scf.portal.welcome_back') }}</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">
            {{ auth('portal')->user()->name }}
        </h1>
        <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('scf.portal.dashboard_subtitle') }}
        </p>
        <div class="mt-5 flex flex-wrap gap-2">
            <flux:button :href="route('portal.orders.index')" variant="primary" icon="shopping-bag" size="sm" wire:navigate>
                {{ __('scf.portal.orders') }}
            </flux:button>
            <flux:button :href="route('portal.invoices.index')" variant="ghost" icon="receipt-percent" size="sm" wire:navigate>
                {{ __('scf.portal.invoices') }}
            </flux:button>
            <flux:button :href="route('portal.tickets.create')" variant="ghost" icon="plus" size="sm" wire:navigate>
                {{ __('scf.portal.new_ticket') }}
            </flux:button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($kpis as $kpi)
            <div class="portal-kpi">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('scf.portal.kpi_'.$kpi['key']) }}</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">
                            {{ ! empty($kpi['money']) ? number_format((float) $kpi['value'], 2) : number_format((float) $kpi['value']) }}
                        </p>
                    </div>
                    <div class="flex size-10 items-center justify-center rounded-xl bg-sky-500/10 text-sky-700 dark:text-sky-300">
                        <flux:icon :name="$kpi['icon']" class="size-5" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="portal-glass rounded-2xl p-5 lg:col-span-2">
            <flux:heading size="lg">{{ __('scf.portal.recent_activity') }}</flux:heading>
            <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->recentActivity as $item)
                    <a href="{{ $item['url'] }}" class="flex items-center justify-between gap-3 py-3 hover:opacity-80" wire:navigate>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item['label'] }}</p>
                            <p class="text-xs capitalize text-zinc-500">{{ $item['type'] }} · {{ $item['status'] }}</p>
                        </div>
                        <span class="shrink-0 text-xs text-zinc-500">{{ $item['at']?->diffForHumans() }}</span>
                    </a>
                @empty
                    <x-empty-state icon="clock" :title="__('scf.portal.no_activity')" :description="__('scf.portal.dashboard_subtitle')" />
                @endforelse
            </div>
        </div>

        <div class="portal-glass rounded-2xl p-5">
            <flux:heading size="lg">{{ __('scf.portal.quick_actions') }}</flux:heading>
            <div class="mt-4 grid gap-1.5">
                <flux:button :href="route('portal.quotations.index')" variant="ghost" icon="document-duplicate" class="justify-start" wire:navigate>
                    {{ __('scf.portal.quotations') }}
                </flux:button>
                <flux:button :href="route('portal.payments.index')" variant="ghost" icon="credit-card" class="justify-start" wire:navigate>
                    {{ __('scf.portal.payments') }}
                </flux:button>
                <flux:button :href="route('portal.loyalty.index')" variant="ghost" icon="gift" class="justify-start" wire:navigate>
                    {{ __('scf.portal.loyalty') }}
                </flux:button>
                <flux:button :href="route('portal.documents.index')" variant="ghost" icon="folder" class="justify-start" wire:navigate>
                    {{ __('scf.portal.documents') }}
                </flux:button>
            </div>
        </div>
    </div>
</section>
