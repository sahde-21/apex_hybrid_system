<?php

use App\Models\PosRegister;
use App\Services\Pos\PosShiftService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('POS Daily Summary')] class extends Component {
    #[Url]
    public string $date = '';

    public ?int $registerId = null;

    public function mount(): void
    {
        $this->date = $this->date ?: now()->toDateString();
    }

    /**
     * @return array<string, float|int>
     */
    #[Computed]
    public function summary(): array
    {
        return app(PosShiftService::class)->dailySummary($this->date, $this->registerId);
    }

    #[Computed]
    public function registers()
    {
        return PosRegister::query()->orderBy('name')->get();
    }
}; ?>

<section class="w-full space-y-6">
    <x-page-header :title="__('scf.pos_daily_summary')" :subtitle="__('Daily sales, returns, tax, and discount totals.')">
        <x-slot:actions>
            <flux:button variant="primary" :href="route('pos.terminal')" wire:navigate>{{ __('Open POS') }}</flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="flex flex-wrap gap-3">
        <flux:input wire:model.live="date" type="date" :label="__('Date')" />
        <flux:select wire:model.live="registerId" :label="__('Register')">
            <flux:select.option value="">{{ __('All registers') }}</flux:select.option>
            @foreach ($this->registers as $register)
                <flux:select.option :value="$register->id">{{ $register->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Sales') }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ $this->summary['sales_count'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Gross sales') }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($this->summary['gross_sales'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Returns') }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($this->summary['returns_total'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Net sales') }}</div>
            <div class="mt-1 text-2xl font-semibold text-sky-700 dark:text-sky-300">{{ number_format($this->summary['net_sales'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Tax') }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($this->summary['tax_total'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Discounts') }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($this->summary['discount_total'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Returns count') }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ $this->summary['returns_count'] }}</div>
        </div>
    </div>
</section>
