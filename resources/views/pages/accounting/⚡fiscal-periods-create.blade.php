<?php

use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use App\Services\Accounting\FiscalPeriodService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Fiscal Period')] class extends Component {
    public ?int $fiscal_year_id = null;
    public string $name = '';
    public string $period_number = '1';
    public string $starts_on = '';
    public string $ends_on = '';

    public function mount(): void
    {
        $this->authorize('create', FiscalPeriod::class);
        $year = FiscalYear::query()->orderByDesc('starts_on')->first();
        $this->fiscal_year_id = $year?->id;
        $this->starts_on = now()->startOfMonth()->toDateString();
        $this->ends_on = now()->endOfMonth()->toDateString();
        $this->name = now()->format('Y-m');
        $this->period_number = (string) now()->month;
    }

    #[Computed]
    public function years()
    {
        return FiscalYear::query()->orderByDesc('starts_on')->get(['id', 'name']);
    }

    public function save(): void
    {
        $this->authorize('create', FiscalPeriod::class);

        $validated = $this->validate([
            'fiscal_year_id' => ['required', 'exists:fiscal_years,id'],
            'name' => ['required', 'string', 'max:120'],
            'period_number' => ['required', 'integer', 'min:1', 'max:24'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ]);

        app(FiscalPeriodService::class)->create(auth()->user(), $validated);

        Flux::toast(variant: 'success', text: __('scf.accounting_engine.period_created_toast'));
        $this->redirect(route('fiscal-periods.index'), navigate: true);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="xl">{{ __('scf.accounting_engine.create_period') }}</flux:heading>
        <flux:subheading>{{ __('scf.accounting_engine.fiscal_periods_subtitle') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="portal-glass grid max-w-2xl gap-5 rounded-2xl p-5">
        <flux:select wire:model="fiscal_year_id" :label="__('scf.accounting_engine.fiscal_year')" required>
            @foreach ($this->years as $year)
                <flux:select.option :value="$year->id">{{ $year->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="period_number" type="number" min="1" max="24" :label="__('scf.accounting_engine.period_number')" required />
        <flux:input wire:model="starts_on" type="date" :label="__('scf.accounting_engine.starts_on')" required />
        <flux:input wire:model="ends_on" type="date" :label="__('scf.accounting_engine.ends_on')" required />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            <flux:button :href="route('fiscal-periods.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
