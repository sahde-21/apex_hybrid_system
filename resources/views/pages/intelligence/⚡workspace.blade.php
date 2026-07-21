<?php

use App\Models\Branch;
use App\Services\Alerts\SmartAlertService;
use App\Services\Intelligence\DomainAnalyticsService;
use App\Services\Intelligence\ExecutiveAnalyticsService;
use App\Services\Intelligence\SmartAssistantService;
use App\Services\Recommendations\RecommendationEngine;
use App\Support\Analytics\AnalyticsFilter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Intelligence')] class extends Component {
    public string $tab = 'executive';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public ?int $branch_id = null;

    public string $assistantQuestion = '';

    public function mount(): void
    {
        $this->tab = request()->route()->getName() ? str_replace('intelligence.', '', (string) request()->route()->getName()) : 'executive';
        $this->from = $this->from ?: now()->subDays((int) config('intelligence.default_date_range_days', 30))->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    public function updated($name): void
    {
        unset($this->payload, $this->alerts, $this->recommendations, $this->assistantResponse);
    }

    public function askAssistant(): void
    {
        unset($this->assistantResponse);
    }

    #[Computed]
    public function filter(): AnalyticsFilter
    {
        return AnalyticsFilter::fromArray([
            'from' => $this->from,
            'to' => $this->to,
            'branch_id' => $this->branch_id,
        ]);
    }

    #[Computed]
    public function payload(): array
    {
        $user = auth()->user();

        return match ($this->tab) {
            'executive', 'forecasts' => app(ExecutiveAnalyticsService::class)->dashboard($user, $this->filter),
            'alerts' => ['items' => app(SmartAlertService::class)->activeForUser($user)->all(), 'meta' => ['generated_at' => now()->toIso8601String()]],
            'recommendations' => ['items' => app(RecommendationEngine::class)->activeForUser($user)->all(), 'meta' => ['generated_at' => now()->toIso8601String()]],
            default => app(DomainAnalyticsService::class)->forDomain($user, $this->tab, $this->filter),
        };
    }

    #[Computed]
    public function assistantResponse(): ?array
    {
        if ($this->tab !== 'assistant' || trim($this->assistantQuestion) === '') {
            return null;
        }

        return app(SmartAssistantService::class)->ask(auth()->user(), $this->assistantQuestion, $this->filter);
    }

    #[Computed]
    public function branches()
    {
        return Branch::query()->orderBy('name')->get(['id', 'name']);
    }

    public function acknowledgeAlert(int $id): void
    {
        $alert = \App\Models\IntelligenceAlert::query()->findOrFail($id);
        app(SmartAlertService::class)->acknowledge(auth()->user(), $alert);
        unset($this->payload);
    }

    public function dismissAlert(int $id): void
    {
        $alert = \App\Models\IntelligenceAlert::query()->findOrFail($id);
        app(SmartAlertService::class)->dismiss(auth()->user(), $alert);
        unset($this->payload);
    }
}; ?>

@php
    $data = $this->payload;
    $meta = $data['meta'] ?? ['generated_at' => now()->toIso8601String()];
@endphp

<section class="scf-page space-y-6" dir="auto">
    <x-page-header
        :title="__('scf.intelligence.'.$tab.'_title')"
        :subtitle="__('scf.intelligence.'.$tab.'_subtitle')"
    />

    @if (! in_array($tab, ['alerts', 'recommendations', 'assistant']))
        <div class="grid gap-4 md:grid-cols-4">
            <flux:input type="date" wire:model.live="from" :label="__('scf.intelligence.date_from')" />
            <flux:input type="date" wire:model.live="to" :label="__('scf.intelligence.date_to')" />
            <flux:select wire:model.live="branch_id" :label="__('scf.intelligence.branch')">
                <flux:select.option value="">{{ __('scf.intelligence.all_branches') }}</flux:select.option>
                @foreach ($this->branches as $branch)
                    <flux:select.option :value="$branch->id">{{ $branch->name }}</flux:select.option>
                @endforeach
            </flux:select>
            @can('intelligence.export')
                <div class="flex items-end gap-2">
                    <flux:button size="sm" :href="route('intelligence.export.csv', ['domain' => $tab === 'executive' || $tab === 'forecasts' ? 'executive' : $tab, 'from' => $from, 'to' => $to])" icon="arrow-down-tray">{{ __('scf.intelligence.export_csv') }}</flux:button>
                </div>
            @endcan
        </div>
    @endif

  <flux:text class="text-xs text-zinc-500">{{ __('scf.intelligence.data_freshness', ['time' => $meta['generated_at'] ?? now()->toIso8601String()]) }}</flux:text>

    @if ($tab === 'assistant')
        <flux:card class="space-y-4">
            <flux:text>{{ __('scf.intelligence.assistant_disclaimer') }}</flux:text>
            <flux:input wire:model="assistantQuestion" :placeholder="__('scf.intelligence.assistant_placeholder')" wire:keydown.enter="askAssistant" />
            <flux:button wire:click="askAssistant" variant="primary">{{ __('scf.intelligence.assistant_ask') }}</flux:button>
            @if ($this->assistantResponse)
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    @if (! ($this->assistantResponse['supported'] ?? true))
                        <flux:text>{{ $this->assistantResponse['message'] ?? '' }}</flux:text>
                    @else
                        <pre class="whitespace-pre-wrap text-sm">{{ json_encode($this->assistantResponse['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                </div>
            @endif
        </flux:card>
    @elseif (in_array($tab, ['alerts', 'recommendations']))
        <div class="space-y-3">
            @forelse ($data['items'] ?? [] as $item)
                <flux:card class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <flux:heading size="sm">{{ $item->title }}</flux:heading>
                        <flux:badge>{{ $item->severity->value ?? $item->severity }}</flux:badge>
                    </div>
                    <flux:text>{{ $item->summary ?? $item->description }}</flux:text>
                    @if ($tab === 'alerts')
                        <div class="flex gap-2">
                            <flux:button size="sm" wire:click="acknowledgeAlert({{ $item->id }})">{{ __('scf.intelligence.acknowledge') }}</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="dismissAlert({{ $item->id }})">{{ __('scf.intelligence.dismiss') }}</flux:button>
                        </div>
                    @endif
                </flux:card>
            @empty
                <x-empty-state icon="bell" :title="__('scf.intelligence.empty_'.$tab)" />
            @endforelse
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($data['kpis'] ?? [] as $key => $value)
                <flux:card>
                    <flux:subheading>{{ __('scf.intelligence.kpi_'.$key) !== 'scf.intelligence.kpi_'.$key ? __('scf.intelligence.kpi_'.$key) : str_replace('_', ' ', ucfirst($key)) }}</flux:subheading>
                    <flux:heading size="lg">{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</flux:heading>
                </flux:card>
            @endforeach
        </div>

        @if (isset($data['health_score']))
            <flux:card>
                <flux:heading size="lg">{{ __('scf.intelligence.health_score_title') }}</flux:heading>
                <flux:text>{{ __('scf.intelligence.health_score_value', ['score' => $data['health_score']['score'] ?? '—', 'label' => $data['health_score']['label'] ?? '']) }}</flux:text>
                <flux:text class="text-xs">{{ $data['health_score']['disclaimer'] ?? '' }}</flux:text>
            </flux:card>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($data['charts'] ?? [] as $key => $chart)
                @if (! empty($chart['datasets'] ?? null))
                    <flux:card wire:ignore>
                        <flux:heading size="sm" class="mb-3">{{ __('scf.intelligence.chart_'.$key) !== 'scf.intelligence.chart_'.$key ? __('scf.intelligence.chart_'.$key) : str_replace('_', ' ', ucfirst($key)) }}</flux:heading>
                        <canvas x-data="biChart(@js($chart))" class="max-h-72 w-full"></canvas>
                    </flux:card>
                @endif
            @endforeach
        </div>

        @if ($tab === 'forecasts' || isset($data['revenue_forecast']))
            <flux:card>
                <flux:heading size="lg">{{ __('scf.intelligence.forecast_title') }}</flux:heading>
                <flux:badge color="amber">{{ __('scf.intelligence.estimated_value') }}</flux:badge>
                <flux:text class="mt-2">{{ __('scf.intelligence.forecast_method', ['method' => $data['revenue_forecast']['method'] ?? '—']) }}</flux:text>
            </flux:card>
        @endif

        @if (isset($data['rfm_segments']))
            <flux:card>
                <flux:heading size="lg">{{ __('scf.intelligence.rfm_title') }}</flux:heading>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($data['rfm_segments'] as $segment => $count)
                        <div class="flex justify-between text-sm"><span>{{ __('scf.intelligence.rfm_'.$segment) }}</span><span>{{ $count }}</span></div>
                    @endforeach
                </div>
            </flux:card>
        @endif
    @endif
</section>
