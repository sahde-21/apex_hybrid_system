<?php

use App\Services\Supplier\SupplierDashboardService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.supplier')] #[Title('Supplier Dashboard')] class extends Component {
    #[Computed]
    public function metrics(): array
    {
        return app(SupplierDashboardService::class)->metrics(auth('supplier')->user());
    }

    #[Computed]
    public function recentActivity()
    {
        return app(SupplierDashboardService::class)->recentActivity(auth('supplier')->user());
    }
}; ?>

@php
    $m = $this->metrics;
    $kpis = [
        ['key' => 'purchase_orders', 'value' => $m['purchase_orders'], 'icon' => 'clipboard-document-list'],
        ['key' => 'bills', 'value' => $m['bills'], 'icon' => 'receipt-percent'],
        ['key' => 'payments', 'value' => $m['payments'], 'icon' => 'banknotes'],
        ['key' => 'outstanding', 'value' => $m['outstanding'], 'icon' => 'scale', 'money' => true],
        ['key' => 'contracts', 'value' => $m['contracts'], 'icon' => 'document-text'],
        ['key' => 'deliveries', 'value' => $m['deliveries'], 'icon' => 'truck'],
    ];
@endphp

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-6 sm:p-8">
        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ __('scf.supplier_portal.welcome_back') }}</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">
            {{ auth('supplier')->user()->name }}
        </h1>
        <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('scf.supplier_portal.dashboard_subtitle') }}
        </p>
        <div class="mt-5 flex flex-wrap gap-2">
            <flux:button :href="route('supplier.purchase-orders.index')" variant="primary" icon="clipboard-document-list" size="sm" wire:navigate>
                {{ __('scf.supplier_portal.purchase_orders') }}
            </flux:button>
            <flux:button :href="route('supplier.bills.index')" variant="ghost" icon="receipt-percent" size="sm" wire:navigate>
                {{ __('scf.supplier_portal.bills') }}
            </flux:button>
            <flux:button :href="route('supplier.deliveries.index')" variant="ghost" icon="truck" size="sm" wire:navigate>
                {{ __('scf.supplier_portal.deliveries') }}
            </flux:button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($kpis as $kpi)
            <div class="portal-kpi">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('scf.supplier_portal.kpi_'.$kpi['key']) }}</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">
                            {{ ! empty($kpi['money']) ? number_format((float) $kpi['value'], 2) : number_format((float) $kpi['value']) }}
                        </p>
                    </div>
                    <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                        <flux:icon :name="$kpi['icon']" class="size-5" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="portal-glass rounded-2xl p-5 lg:col-span-2">
            <flux:heading size="lg">{{ __('scf.supplier_portal.recent_activity') }}</flux:heading>
            <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->recentActivity as $item)
                    <a href="{{ $item['url'] }}" class="flex items-center justify-between gap-3 py-3 hover:opacity-80" wire:navigate>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item['label'] }}</p>
                            <p class="text-xs capitalize text-zinc-500">{{ str_replace('_', ' ', $item['type']) }} · {{ $item['status'] }}</p>
                        </div>
                        <span class="shrink-0 text-xs text-zinc-500">{{ $item['at']?->diffForHumans() }}</span>
                    </a>
                @empty
                    <x-empty-state icon="clock" :title="__('scf.supplier_portal.no_activity')" :description="__('scf.supplier_portal.dashboard_subtitle')" />
                @endforelse
            </div>
        </div>

        <div class="portal-glass rounded-2xl p-5">
            <flux:heading size="lg">{{ __('scf.supplier_portal.quick_actions') }}</flux:heading>
            <div class="mt-4 grid gap-1.5">
                <flux:button :href="route('supplier.payments.index')" variant="ghost" icon="credit-card" class="justify-start" wire:navigate>
                    {{ __('scf.supplier_portal.payments') }}
                </flux:button>
                <flux:button :href="route('supplier.contracts.index')" variant="ghost" icon="document-text" class="justify-start" wire:navigate>
                    {{ __('scf.supplier_portal.contracts') }}
                </flux:button>
                <flux:button :href="route('supplier.documents.index')" variant="ghost" icon="folder" class="justify-start" wire:navigate>
                    {{ __('scf.supplier_portal.documents') }}
                </flux:button>
                <flux:button :href="route('supplier.notifications.index')" variant="ghost" icon="bell" class="justify-start" wire:navigate>
                    {{ __('scf.supplier_portal.notifications') }}
                    @if ($m['unread_notifications'] > 0)
                        <flux:badge size="sm" color="red" class="ms-auto">{{ $m['unread_notifications'] }}</flux:badge>
                    @endif
                </flux:button>
            </div>
        </div>
    </div>
</section>
