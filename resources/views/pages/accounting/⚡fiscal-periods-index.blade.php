<?php

use App\Enums\FiscalPeriodStatus;
use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use App\Services\Accounting\FiscalPeriodService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Fiscal Periods')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', FiscalPeriod::class);
    }

    #[Computed]
    public function currentPeriod(): ?FiscalPeriod
    {
        return app(FiscalPeriodService::class)->currentPeriod();
    }

    #[Computed]
    public function periods()
    {
        return FiscalPeriod::query()
            ->with(['fiscalYear:id,name,status', 'closedBy:id,name'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('starts_on')
            ->paginate(24);
    }

    public function close(int $periodId): void
    {
        $period = FiscalPeriod::query()->findOrFail($periodId);
        $this->authorize('manage', $period);
        app(FiscalPeriodService::class)->closePeriod($period, auth()->user());
        Flux::toast(variant: 'success', text: __('scf.accounting_engine.period_closed_toast'));
        unset($this->periods, $this->currentPeriod);
    }

    public function lock(int $periodId): void
    {
        $period = FiscalPeriod::query()->findOrFail($periodId);
        $this->authorize('manage', $period);
        app(FiscalPeriodService::class)->lockPeriod($period, auth()->user());
        Flux::toast(variant: 'success', text: __('scf.accounting_engine.period_locked_toast'));
        unset($this->periods, $this->currentPeriod);
    }

    public function reopen(int $periodId): void
    {
        $period = FiscalPeriod::query()->findOrFail($periodId);
        $this->authorize('manage', $period);
        app(FiscalPeriodService::class)->reopenPeriod($period, auth()->user());
        Flux::toast(variant: 'success', text: __('scf.accounting_engine.period_reopened_toast'));
        unset($this->periods, $this->currentPeriod);
    }

    public function delete(int $periodId): void
    {
        $period = FiscalPeriod::query()->findOrFail($periodId);
        $this->authorize('delete', $period);
        app(FiscalPeriodService::class)->delete($period, auth()->user());
        Flux::toast(variant: 'success', text: __('scf.accounting_engine.period_deleted_toast'));
        unset($this->periods, $this->currentPeriod);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('scf.accounting_engine.fiscal_periods_title') }}</flux:heading>
                <flux:subheading>{{ __('scf.accounting_engine.fiscal_periods_subtitle') }}</flux:subheading>
            </div>
            @can('create', App\Models\FiscalPeriod::class)
                <flux:button variant="primary" :href="route('fiscal-periods.create')" wire:navigate icon="plus">
                    {{ __('scf.accounting_engine.create_period') }}
                </flux:button>
            @endcan
        </div>

        @if ($this->currentPeriod)
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100">
                <span class="font-medium">{{ __('scf.accounting_engine.current_period') }}:</span>
                {{ $this->currentPeriod->name }}
                ({{ $this->currentPeriod->starts_on->toDateString() }} → {{ $this->currentPeriod->ends_on->toDateString() }})
                — {{ $this->currentPeriod->status->label() }}
            </div>
        @endif

        <div class="mt-4 max-w-xs">
            <flux:select wire:model.live="status" :label="__('Status')">
                <flux:select.option value="">{{ __('All') }}</flux:select.option>
                @foreach (FiscalPeriodStatus::cases() as $case)
                    <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.fiscal_year') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.starts_on') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.ends_on') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->periods as $period)
                    <tr wire:key="period-{{ $period->id }}">
                        <td class="px-4 py-3 font-medium">
                            {{ $period->name }}
                            @if ($this->currentPeriod?->id === $period->id)
                                <span class="ms-2 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">{{ __('scf.accounting_engine.current') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $period->fiscalYear?->name }}</td>
                        <td class="px-4 py-3">{{ $period->starts_on->toDateString() }}</td>
                        <td class="px-4 py-3">{{ $period->ends_on->toDateString() }}</td>
                        <td class="px-4 py-3">{{ $period->status->label() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-1">
                                @can('update', $period)
                                    @if ($period->status === FiscalPeriodStatus::Open)
                                        <flux:button size="sm" variant="ghost" :href="route('fiscal-periods.edit', $period)" wire:navigate>{{ __('Edit') }}</flux:button>
                                    @endif
                                @endcan
                                @can('manage', $period)
                                    @if ($period->status === FiscalPeriodStatus::Open)
                                        <flux:button size="sm" variant="ghost" wire:click="close({{ $period->id }})" wire:confirm="{{ __('scf.accounting_engine.confirm_close_period') }}">{{ __('scf.accounting_engine.close') }}</flux:button>
                                        <flux:button size="sm" variant="ghost" wire:click="lock({{ $period->id }})" wire:confirm="{{ __('scf.accounting_engine.confirm_lock_period') }}">{{ __('scf.accounting_engine.lock') }}</flux:button>
                                    @else
                                        <flux:button size="sm" variant="ghost" wire:click="reopen({{ $period->id }})" wire:confirm="{{ __('scf.accounting_engine.confirm_reopen_period') }}">{{ __('scf.accounting_engine.reopen') }}</flux:button>
                                    @endif
                                @endcan
                                @can('delete', $period)
                                    @if ($period->status === FiscalPeriodStatus::Open)
                                        <flux:button size="sm" variant="danger" wire:click="delete({{ $period->id }})" wire:confirm="{{ __('Are you sure?') }}">{{ __('Delete') }}</flux:button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">{{ __('scf.no_records') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $this->periods->links() }}</div>
    </div>
</section>
