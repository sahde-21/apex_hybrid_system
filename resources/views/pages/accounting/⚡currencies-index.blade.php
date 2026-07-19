<?php

use App\Models\Currency;
use App\Services\Accounting\CurrencyService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Currencies')] class extends Component {
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Currency::class);
    }

    #[Computed]
    public function currencies()
    {
        return Currency::query()
            ->withCount('rates')
            ->orderByDesc('is_base')
            ->orderBy('code')
            ->paginate(25);
    }

    public function setBase(int $currencyId): void
    {
        $currency = Currency::query()->findOrFail($currencyId);
        $this->authorize('update', $currency);
        app(CurrencyService::class)->setBase($currency, auth()->user());
        Flux::toast(variant: 'success', text: __('scf.accounting_engine.base_currency_updated'));
        unset($this->currencies);
    }

    public function delete(int $currencyId): void
    {
        $currency = Currency::query()->findOrFail($currencyId);
        $this->authorize('delete', $currency);
        app(CurrencyService::class)->delete($currency, auth()->user());
        Flux::toast(variant: 'success', text: __('scf.accounting_engine.currency_deleted_toast'));
        unset($this->currencies);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('scf.accounting_engine.currencies_title') }}</flux:heading>
                <flux:subheading>{{ __('scf.accounting_engine.currencies_subtitle') }}</flux:subheading>
            </div>
            @can('create', App\Models\Currency::class)
                <flux:button variant="primary" :href="route('currencies.create')" wire:navigate icon="plus">
                    {{ __('scf.accounting_engine.create_currency') }}
                </flux:button>
            @endcan
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.code') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.symbol') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.exchange_rates') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->currencies as $currency)
                    <tr wire:key="currency-{{ $currency->id }}">
                        <td class="px-4 py-3 font-mono">
                            {{ $currency->code }}
                            @if ($currency->is_base)
                                <span class="ms-2 rounded bg-sky-50 px-1.5 py-0.5 text-[10px] text-sky-700 dark:bg-sky-950 dark:text-sky-300">{{ __('scf.accounting_engine.base') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $currency->name }}</td>
                        <td class="px-4 py-3">{{ $currency->symbol }}</td>
                        <td class="px-4 py-3">{{ $currency->is_active ? __('Active') : __('Inactive') }}</td>
                        <td class="px-4 py-3">{{ $currency->rates_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-1">
                                @can('update', $currency)
                                    <flux:button size="sm" variant="ghost" :href="route('currencies.edit', $currency)" wire:navigate>{{ __('Edit') }}</flux:button>
                                    @unless ($currency->is_base)
                                        <flux:button size="sm" variant="ghost" wire:click="setBase({{ $currency->id }})">{{ __('scf.accounting_engine.set_base') }}</flux:button>
                                    @endunless
                                    <flux:button size="sm" variant="ghost" :href="route('currencies.rates', $currency)" wire:navigate>{{ __('scf.accounting_engine.manage_rates') }}</flux:button>
                                @endcan
                                @can('delete', $currency)
                                    <flux:button size="sm" variant="danger" wire:click="delete({{ $currency->id }})" wire:confirm="{{ __('Are you sure?') }}">{{ __('Delete') }}</flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $this->currencies->links() }}</div>
    </div>
</section>
