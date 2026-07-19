<?php

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\Warehouse;
use App\Services\Bi\BiAnalyticsService;
use App\Support\Bi\BiFilter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Executive Analytics')] class extends Component {
    #[Url]
    public string $dashboard = 'ceo';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public ?int $branch_id = null;

    #[Url]
    public ?int $warehouse_id = null;

    #[Url]
    public ?int $customer_id = null;

    #[Url]
    public ?int $supplier_id = null;

    #[Url]
    public ?int $employee_id = null;

    #[Url]
    public string $currency = 'IQD';

    public function mount(): void
    {
        $this->authorize('analytics.read');
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();

        if (! array_key_exists($this->dashboard, config('bi.dashboards', []))) {
            $this->dashboard = 'ceo';
        }
    }

    public function updated($name): void
    {
        unset($this->payload);
    }

    protected function filter(): BiFilter
    {
        return BiFilter::fromArray([
            'from' => $this->from,
            'to' => $this->to,
            'branch_id' => $this->branch_id,
            'warehouse_id' => $this->warehouse_id,
            'customer_id' => $this->customer_id,
            'supplier_id' => $this->supplier_id,
            'employee_id' => $this->employee_id,
            'currency' => $this->currency,
            'dashboard' => $this->dashboard,
        ]);
    }

    #[Computed]
    public function payload(): array
    {
        return app(BiAnalyticsService::class)->dashboard(auth()->user(), $this->filter());
    }

    #[Computed]
    public function branches()
    {
        return Branch::query()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function customers()
    {
        return Contact::query()->whereIn('type', ['customer', 'both'])->orderBy('name')->limit(100)->get(['id', 'name']);
    }

    #[Computed]
    public function suppliers()
    {
        return Contact::query()->whereIn('type', ['supplier', 'both'])->orderBy('name')->limit(100)->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(100)
            ->get(['id', 'first_name', 'last_name']);
    }
}; ?>

@php
    $payload = $this->payload;
    $kpis = $payload['kpis'];
    $charts = $payload['charts'];
    $rankings = $payload['rankings'];
    $primaryKpis = config('bi.dashboards.'.$dashboard.'.kpis', [
        'revenue', 'net_profit', 'gross_profit', 'sales_today', 'sales_week', 'sales_month',
        'expenses', 'cash_flow', 'outstanding_invoices', 'outstanding_bills',
        'payroll_cost', 'inventory_value', 'low_stock',
    ]);
    $countKpis = ['low_stock', 'open_leads', 'open_tickets', 'production_orders', 'employees', 'customers'];
@endphp

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-sky-700 dark:text-sky-300">{{ __('scf.bi.brand') }}</p>
                <flux:heading size="xl">{{ __('scf.bi.hub_title') }}</flux:heading>
                <flux:subheading>{{ __('scf.bi.hub_subtitle') }}</flux:subheading>
            </div>
            <flux:button :href="route('analytics.reports')" variant="primary" icon="document-chart-bar" wire:navigate>
                {{ __('scf.bi.reports') }}
            </flux:button>
        </div>

        <div class="mt-5 flex flex-wrap gap-2">
            @foreach (array_keys(config('bi.dashboards')) as $pack)
                <flux:button
                    size="sm"
                    wire:click="$set('dashboard', '{{ $pack }}')"
                    :variant="$dashboard === $pack ? 'primary' : 'ghost'"
                >
                    {{ __('scf.bi.dashboard_'.$pack) }}
                </flux:button>
            @endforeach
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-6">
            <flux:input type="date" wire:model.live="from" :label="__('scf.bi.filter_from')" />
            <flux:input type="date" wire:model.live="to" :label="__('scf.bi.filter_to')" />
            <flux:select wire:model.live="branch_id" :label="__('scf.bi.filter_branch')">
                <option value="">{{ __('All') }}</option>
                @foreach ($this->branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="warehouse_id" :label="__('scf.bi.filter_warehouse')">
                <option value="">{{ __('All') }}</option>
                @foreach ($this->warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="customer_id" :label="__('scf.bi.filter_customer')">
                <option value="">{{ __('All') }}</option>
                @foreach ($this->customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="supplier_id" :label="__('scf.bi.filter_supplier')">
                <option value="">{{ __('All') }}</option>
                @foreach ($this->suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="employee_id" :label="__('scf.bi.filter_employee')">
                <option value="">{{ __('All') }}</option>
                @foreach ($this->employees as $employee)
                    <option value="{{ $employee->id }}">{{ trim($employee->first_name.' '.$employee->last_name) }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="currency" :label="__('scf.bi.filter_currency')">
                <option value="IQD">IQD</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
            </flux:select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @foreach ($primaryKpis as $key)
            <div class="portal-kpi" wire:key="kpi-{{ $dashboard }}-{{ $key }}">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('scf.bi.kpi_'.$key) }}</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">
                    @if (in_array($key, $countKpis, true))
                        {{ number_format((float) ($kpis[$key] ?? 0)) }}
                    @else
                        {{ number_format((float) ($kpis[$key] ?? 0), 2) }}
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        @foreach ($charts as $chartKey => $chart)
            <div class="portal-glass rounded-2xl p-5" wire:key="chart-{{ $dashboard }}-{{ $from }}-{{ $to }}-{{ $chartKey }}">
                <flux:heading size="sm">{{ __('scf.bi.chart_'.$chartKey) }}</flux:heading>
                @if (($chart['type'] ?? '') === 'heatmap')
                    <div class="mt-4 grid grid-cols-7 gap-1">
                        @foreach (($chart['meta']['cells'] ?? []) as $cell)
                            @php
                                $intensity = min(1, ((int) $cell['value']) / 10);
                            @endphp
                            <div
                                class="aspect-square rounded-md"
                                style="background: color-mix(in oklab, rgb(14 165 233) {{ (int) ($intensity * 100) }}%, rgb(241 245 249));"
                                title="{{ $cell['date'] }}: {{ $cell['value'] }}"
                            ></div>
                        @endforeach
                    </div>
                @else
                    <div
                        class="mt-4 h-72"
                        wire:ignore
                        x-data="biChart(@js($chart))"
                        x-init="render()"
                    >
                        <canvas x-ref="canvas" class="max-h-72 w-full"></canvas>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        @foreach (['top_products', 'top_customers', 'top_suppliers', 'top_employees', 'top_branches'] as $rankKey)
            @continue(! isset($rankings[$rankKey]))
            <div class="portal-glass rounded-2xl p-5" wire:key="rank-{{ $dashboard }}-{{ $rankKey }}">
                <flux:heading size="sm">{{ __('scf.bi.'.$rankKey) }}</flux:heading>
                <div class="mt-4 h-64" wire:ignore x-data="biChart(@js($rankings[$rankKey]))" x-init="render()">
                    <canvas x-ref="canvas" class="max-h-64 w-full"></canvas>
                </div>
            </div>
        @endforeach
    </div>
</section>
