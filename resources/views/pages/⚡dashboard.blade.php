<?php

use App\Services\Dashboard\DashboardMetricsService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public function title(): string
    {
        return __('scf.dashboard');
    }

    /**
     * @return array<string, int|float>
     */
    #[Computed]
    public function metrics(): array
    {
        return app(DashboardMetricsService::class)->metrics();
    }

    /**
     * @return array<int, array{label: string, value: float|int, max: float|int}>
     */
    #[Computed]
    public function snapshotBars(): array
    {
        return app(DashboardMetricsService::class)->snapshotBars($this->metrics);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\AuditLog>
     */
    #[Computed]
    public function recentActivity()
    {
        return app(DashboardMetricsService::class)->recentActivity();
    }
}; ?>

@php
    $m = $this->metrics;
    $kpis = [
        ['key' => 'products', 'value' => $m['products'], 'icon' => 'cube', 'tone' => 'sky'],
        ['key' => 'contacts', 'value' => $m['contacts'], 'icon' => 'users', 'tone' => 'indigo'],
        ['key' => 'sale_orders', 'value' => $m['sale_orders'], 'icon' => 'shopping-bag', 'tone' => 'emerald'],
        ['key' => 'invoices', 'value' => $m['invoices'], 'icon' => 'receipt-percent', 'tone' => 'violet'],
        ['key' => 'purchase_orders', 'value' => $m['purchase_orders'], 'icon' => 'truck', 'tone' => 'cyan'],
        ['key' => 'payments_total', 'value' => $m['payments_total'], 'icon' => 'banknotes', 'tone' => 'teal', 'money' => true],
        ['key' => 'expenses', 'value' => $m['expenses_total'], 'icon' => 'credit-card', 'tone' => 'rose', 'money' => true],
        ['key' => 'employees', 'value' => $m['employees'], 'icon' => 'identification', 'tone' => 'blue'],
        ['key' => 'open_leads', 'value' => $m['open_leads'], 'icon' => 'sparkles', 'tone' => 'amber'],
        ['key' => 'production_orders', 'value' => $m['production_orders'], 'icon' => 'cog-6-tooth', 'tone' => 'slate'],
        ['key' => 'stock_transfers', 'value' => $m['stock_transfers'], 'icon' => 'arrows-right-left', 'tone' => 'orange'],
        ['key' => 'open_tickets', 'value' => $m['open_tickets'], 'icon' => 'ticket', 'tone' => 'fuchsia'],
        ['key' => 'low_stock', 'value' => $m['low_stock_products'], 'icon' => 'exclamation-triangle', 'tone' => 'amber', 'warn' => true],
    ];

    $toneClasses = [
        'sky' => 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
        'indigo' => 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
        'emerald' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        'violet' => 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
        'cyan' => 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
        'teal' => 'bg-teal-500/10 text-teal-700 dark:text-teal-300',
        'rose' => 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
        'blue' => 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
        'amber' => 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
        'slate' => 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
        'orange' => 'bg-orange-500/10 text-orange-700 dark:text-orange-300',
        'fuchsia' => 'bg-fuchsia-500/10 text-fuchsia-700 dark:text-fuchsia-300',
    ];
@endphp

<section class="scf-page" wire:loading.class="opacity-60">
    <x-page-header
        :title="__('scf.dashboard_page.welcome')"
        :subtitle="__('scf.dashboard_page.subtitle')"
    >
        <x-slot:actions>
            @can('invoices.read')
                @if (Route::has('invoices.index'))
                    <flux:button :href="route('invoices.index')" variant="primary" icon="plus" size="sm" wire:navigate>
                        {{ __('scf.invoices') }}
                    </flux:button>
                @endif
            @endcan
            @can('products.read')
                @if (Route::has('products.index'))
                    <flux:button :href="route('products.index')" variant="ghost" icon="cube" size="sm" wire:navigate>
                        {{ __('scf.products') }}
                    </flux:button>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div wire:loading.delay.shortest>
        <x-skeleton variant="kpi" :cards="4" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" wire:loading.remove.delay.shortest>
        @foreach ($kpis as $index => $kpi)
            <div class="scf-kpi" style="animation-delay: {{ $index * 40 }}ms">
                <div class="flex items-start justify-between gap-3 ps-1">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            {{ __('scf.dashboard_page.'.$kpi['key']) }}
                        </p>
                        <p @class([
                            'scf-stat-value mt-2 text-3xl font-semibold tracking-tight tabular-nums',
                            'text-amber-600 dark:text-amber-400' => ! empty($kpi['warn']),
                            'text-zinc-900 dark:text-white' => empty($kpi['warn']),
                        ]) style="animation-delay: {{ $index * 50 }}ms">
                            {{ ! empty($kpi['money']) ? number_format((float) $kpi['value'], 2) : number_format((int) $kpi['value']) }}
                        </p>
                    </div>
                    <div @class([
                        'flex size-11 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset ring-black/5 dark:ring-white/10',
                        $toneClasses[$kpi['tone']] ?? $toneClasses['slate'],
                    ])>
                        <flux:icon :name="$kpi['icon']" class="size-5" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="scf-card lg:col-span-2">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg" class="tracking-tight">{{ __('scf.dashboard_page.snapshot') }}</flux:heading>
                    <flux:subheading class="mt-1">{{ __('scf.dashboard_page.snapshot_subtitle') }}</flux:subheading>
                </div>
                <div class="flex size-10 items-center justify-center rounded-xl bg-sky-500/10 text-sky-700 dark:text-sky-300">
                    <flux:icon name="chart-bar" class="size-5" />
                </div>
            </div>

            <div class="mt-6 space-y-5">
                @forelse ($this->snapshotBars as $bar)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $bar['label'] }}</span>
                            <span class="tabular-nums text-zinc-500">{{ number_format($bar['value']) }}</span>
                        </div>
                        <div class="scf-chart-track">
                            <div
                                class="scf-chart-bar"
                                style="width: {{ $bar['max'] > 0 ? min(100, round(($bar['value'] / $bar['max']) * 100)) : 0 }}%"
                            ></div>
                        </div>
                    </div>
                @empty
                    <x-empty-state
                        icon="chart-bar"
                        :title="__('scf.dashboard_page.snapshot')"
                        :description="__('scf.dashboard_page.no_activity')"
                    />
                @endforelse
            </div>
        </div>

        <div class="scf-card">
            <flux:heading size="lg" class="tracking-tight">{{ __('scf.dashboard_page.quick_links') }}</flux:heading>
            <div class="mt-4 grid gap-1.5">
                @can('sale-orders.read')
                    @if (Route::has('sale-orders.index'))
                        <flux:button :href="route('sale-orders.index')" variant="ghost" icon="shopping-bag" class="justify-start" wire:navigate>
                            {{ __('scf.dashboard_page.link_sales') }}
                        </flux:button>
                    @endif
                @endcan
                @can('products.read')
                    @if (Route::has('products.index'))
                        <flux:button :href="route('products.index')" variant="ghost" icon="cube" class="justify-start" wire:navigate>
                            {{ __('scf.dashboard_page.link_inventory') }}
                        </flux:button>
                    @endif
                @endcan
                @can('expenses.read')
                    @if (Route::has('expenses.index'))
                        <flux:button :href="route('expenses.index')" variant="ghost" icon="banknotes" class="justify-start" wire:navigate>
                            {{ __('scf.dashboard_page.link_accounting') }}
                        </flux:button>
                    @endif
                @elsecan('invoices.read')
                    @if (Route::has('invoices.index'))
                        <flux:button :href="route('invoices.index')" variant="ghost" icon="banknotes" class="justify-start" wire:navigate>
                            {{ __('scf.dashboard_page.link_accounting') }}
                        </flux:button>
                    @endif
                @endcan
                @can('employees.read')
                    @if (Route::has('employees.index'))
                        <flux:button :href="route('employees.index')" variant="ghost" icon="users" class="justify-start" wire:navigate>
                            {{ __('scf.dashboard_page.link_hr') }}
                        </flux:button>
                    @endif
                @endcan
                @can('tickets.read')
                    @if (Route::has('tickets.index'))
                        <flux:button :href="route('tickets.index')" variant="ghost" icon="ticket" class="justify-start" wire:navigate>
                            {{ __('scf.tickets') }}
                        </flux:button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    <div class="scf-card">
        <div class="flex items-center justify-between gap-3">
            <flux:heading size="lg" class="tracking-tight">{{ __('scf.dashboard_page.recent_activity') }}</flux:heading>
            <flux:badge size="sm" color="zinc">{{ $this->recentActivity->count() }}</flux:badge>
        </div>

        <div class="mt-2 divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($this->recentActivity as $log)
                <div class="scf-activity-item">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            <span class="capitalize">{{ $log->action }}</span>
                            · {{ class_basename($log->auditable_type) }}
                            <span class="text-zinc-500">#{{ $log->auditable_id }}</span>
                        </p>
                        <p class="mt-0.5 text-xs text-zinc-500">
                            {{ __('scf.dashboard_page.by_user', ['name' => $log->user?->name ?? __('scf.dashboard_page.system')]) }}
                        </p>
                    </div>
                    <flux:text class="shrink-0 text-xs text-zinc-500">
                        {{ $log->created_at?->diffForHumans() }}
                    </flux:text>
                </div>
            @empty
                <x-empty-state
                    icon="clock"
                    :title="__('scf.dashboard_page.no_activity')"
                    :description="__('scf.dashboard_page.subtitle')"
                />
            @endforelse
        </div>
    </div>
</section>
