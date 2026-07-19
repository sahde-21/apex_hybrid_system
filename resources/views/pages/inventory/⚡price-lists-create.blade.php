<?php

use App\Concerns\PriceListValidationRules;
use App\Models\PriceList;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Price lists')] class extends Component {
    use PriceListValidationRules;
    public string $name = '';
    public string $code = '';
    public string $currency = 'USD';
    public string $valid_from = '';
    public string $valid_until = '';
    public bool $is_active = true;

    public function mount(): void
    {
    }

    public function save(): void
    {
        $validated = $this->validate($this->priceListRules());

        PriceList::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Price lists created successfully.'));

        $this->redirect(route('price-lists.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Price lists') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="currency" :label="__('Currency')" />
        <flux:input wire:model="valid_from" type="date" :label="__('Valid From')" />
        <flux:input wire:model="valid_until" type="date" :label="__('Valid Until')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('price-lists.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
