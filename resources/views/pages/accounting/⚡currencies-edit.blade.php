<?php

use App\Models\Currency;
use App\Services\Accounting\CurrencyService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Currency')] class extends Component {
    public Currency $currency;
    public string $code = '';
    public string $name = '';
    public string $symbol = '';
    public string $decimal_places = '2';
    public bool $is_base = false;
    public bool $is_active = true;

    public function mount(Currency $currency): void
    {
        $this->authorize('update', $currency);
        $this->currency = $currency;
        $this->code = $currency->code;
        $this->name = $currency->name;
        $this->symbol = $currency->symbol;
        $this->decimal_places = (string) $currency->decimal_places;
        $this->is_base = $currency->is_base;
        $this->is_active = $currency->is_active;
    }

    public function save(): void
    {
        $this->authorize('update', $this->currency);

        $validated = $this->validate([
            'code' => ['required', 'string', 'size:3', 'unique:currencies,code,'.$this->currency->id],
            'name' => ['required', 'string', 'max:120'],
            'symbol' => ['required', 'string', 'max:8'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:8'],
            'is_base' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        app(CurrencyService::class)->update($this->currency, auth()->user(), $validated);

        Flux::toast(variant: 'success', text: __('scf.accounting_engine.currency_updated_toast'));
        $this->redirect(route('currencies.index'), navigate: true);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="xl">{{ __('scf.accounting_engine.edit_currency') }}</flux:heading>
        <flux:subheading>{{ $currency->code }}</flux:subheading>
    </div>

    <form wire:submit="save" class="portal-glass grid max-w-2xl gap-5 rounded-2xl p-5">
        <flux:input wire:model="code" :label="__('scf.accounting_engine.code')" maxlength="3" required />
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="symbol" :label="__('scf.accounting_engine.symbol')" required />
        <flux:input wire:model="decimal_places" type="number" min="0" max="8" :label="__('scf.accounting_engine.decimal_places')" required />
        <flux:checkbox wire:model="is_base" :label="__('scf.accounting_engine.is_base')" :disabled="$currency->is_base" />
        <flux:checkbox wire:model="is_active" :label="__('Active')" :disabled="$currency->is_base" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            <flux:button :href="route('currencies.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
