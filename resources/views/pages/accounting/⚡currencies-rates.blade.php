<?php

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\Accounting\CurrencyService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Exchange Rates')] class extends Component {
    use WithPagination;

    public Currency $currency;
    public string $rate_date = '';
    public string $rate = '1';

    public function mount(Currency $currency): void
    {
        $this->authorize('update', $currency);
        abort_if($currency->is_base, 403);
        $this->currency = $currency;
        $this->rate_date = now()->toDateString();
    }

    #[Computed]
    public function rates()
    {
        return ExchangeRate::query()
            ->where('currency_id', $this->currency->id)
            ->orderByDesc('rate_date')
            ->paginate(20);
    }

    public function saveRate(): void
    {
        $this->authorize('update', $this->currency);

        $validated = $this->validate([
            'rate_date' => ['required', 'date'],
            'rate' => ['required', 'numeric', 'gt:0'],
        ]);

        app(CurrencyService::class)->upsertExchangeRate($this->currency, auth()->user(), $validated);

        Flux::toast(variant: 'success', text: __('scf.accounting_engine.exchange_rate_saved'));
        $this->reset('rate');
        $this->rate = '1';
        unset($this->rates);
    }

    public function deleteRate(int $rateId): void
    {
        $rate = ExchangeRate::query()->where('currency_id', $this->currency->id)->findOrFail($rateId);
        $this->authorize('delete', $rate);
        app(CurrencyService::class)->deleteExchangeRate($rate, auth()->user());
        Flux::toast(variant: 'success', text: __('scf.accounting_engine.exchange_rate_deleted'));
        unset($this->rates);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="xl">{{ __('scf.accounting_engine.exchange_rates_for', ['code' => $currency->code]) }}</flux:heading>
        <flux:subheading>{{ __('scf.accounting_engine.exchange_rates_subtitle') }}</flux:subheading>
        <div class="mt-3">
            <flux:button :href="route('currencies.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <form wire:submit="saveRate" class="portal-glass grid max-w-xl gap-4 rounded-2xl p-5 md:grid-cols-3">
        <flux:input wire:model="rate_date" type="date" :label="__('scf.accounting_engine.rate_date')" required />
        <flux:input wire:model="rate" type="number" step="0.00000001" :label="__('scf.accounting_engine.rate')" required />
        <div class="flex items-end">
            <flux:button type="submit" variant="primary" class="w-full">{{ __('Save') }}</flux:button>
        </div>
    </form>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.rate_date') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.rate') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->rates as $rate)
                    <tr wire:key="rate-{{ $rate->id }}">
                        <td class="px-4 py-3">{{ $rate->rate_date->toDateString() }}</td>
                        <td class="px-4 py-3 font-mono">{{ $rate->rate }}</td>
                        <td class="px-4 py-3 text-end">
                            <flux:button size="sm" variant="danger" wire:click="deleteRate({{ $rate->id }})" wire:confirm="{{ __('Are you sure?') }}">{{ __('Delete') }}</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-zinc-500">{{ __('scf.no_records') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $this->rates->links() }}</div>
    </div>
</section>
