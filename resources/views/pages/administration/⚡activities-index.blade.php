<?php

use App\Enums\ActivityType;
use App\Models\User;
use App\Services\Activity\DocumentTimelineService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Activity Center')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $module = '';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    #[Url]
    public string $user_id = '';

    #[Computed]
    public function activities()
    {
        return app(DocumentTimelineService::class)->globalFeed(auth()->user(), [
            'search' => $this->search ?: null,
            'type' => $this->type ?: null,
            'module' => $this->module ?: null,
            'date_from' => $this->date_from ?: null,
            'date_to' => $this->date_to ?: null,
            'user_id' => $this->user_id !== '' ? (int) $this->user_id : null,
        ], 20);
    }

    #[Computed]
    public function users()
    {
        return User::query()->where('is_active', true)->orderBy('name')->limit(200)->get(['id', 'name']);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedModule(): void
    {
        $this->resetPage();
    }

    public function updatedUserId(): void
    {
        $this->resetPage();
    }

    public function subjectUrl($activity): ?string
    {
        $subject = $activity->subject;
        if (! $subject) {
            return null;
        }
        $routes = config('activity.subject_routes', []);
        $route = $routes[$subject::class] ?? null;
        if (! is_string($route) || ! \Illuminate\Support\Facades\Route::has($route)) {
            return null;
        }

        return route($route, $subject);
    }
}; ?>

<section class="scf-page space-y-6" dir="auto">
    <x-page-header
        :title="__('scf.activity.center_title')"
        :subtitle="__('scf.activity.center_subtitle')"
    />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('scf.activity.search_placeholder')" />
        <flux:select wire:model.live="type" :placeholder="__('scf.activity.filter_type')">
            <flux:select.option value="">{{ __('scf.activity.filter_type') }}</flux:select.option>
            @foreach (ActivityType::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="module" :placeholder="__('scf.activity.filter_module')">
            <flux:select.option value="">{{ __('scf.activity.filter_module') }}</flux:select.option>
            <flux:select.option value="quotations">{{ __('scf.sales') }} / Quotations</flux:select.option>
            <flux:select.option value="sale-orders">Sale orders</flux:select.option>
            <flux:select.option value="invoices">Invoices</flux:select.option>
            <flux:select.option value="payments">Payments</flux:select.option>
            <flux:select.option value="purchase">{{ __('scf.purchasing') }}</flux:select.option>
            <flux:select.option value="bills">Bills</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="user_id" :placeholder="__('scf.activity.filter_user')">
            <flux:select.option value="">{{ __('scf.activity.filter_user') }}</flux:select.option>
            @foreach ($this->users as $user)
                <flux:select.option :value="$user->id">{{ $user->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="date_from" :label="__('scf.activity.date_from')" />
        <flux:input type="date" wire:model.live="date_to" :label="__('scf.activity.date_to')" />
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->activities">
            <flux:table.columns>
                <flux:table.column>{{ __('scf.activity.timestamp') }}</flux:table.column>
                <flux:table.column>{{ __('scf.activity.user') }}</flux:table.column>
                <flux:table.column>{{ __('scf.activity.type') }}</flux:table.column>
                <flux:table.column>{{ __('scf.activity.subject') }}</flux:table.column>
                <flux:table.column>{{ __('scf.activity.message') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->activities as $activity)
                    <flux:table.row wire:key="activity-center-{{ $activity->id }}">
                        <flux:table.cell>{{ $activity->created_at?->format('Y-m-d H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ $activity->user?->name ?? __('scf.activity.system') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$activity->type->color()">{{ $activity->type->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">
                            {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                        </flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate">{{ $activity->title ?: \Illuminate\Support\Str::limit($activity->body, 80) }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($url = $this->subjectUrl($activity))
                                <flux:button size="sm" variant="ghost" :href="$url" wire:navigate>{{ __('scf.activity.open') }}</flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="clock" :title="__('scf.activity.empty_center')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
