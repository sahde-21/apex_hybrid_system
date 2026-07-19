<?php

use App\Concerns\PriceListValidationRules;
use App\Models\PriceList;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Price lists')] class extends Component {
    use PriceListValidationRules;
    public PriceList $priceList;

    public string $name = '';
    public string $code = '';
    public string $currency = 'USD';
    public string $valid_from = '';
    public string $valid_until = '';
    public bool $is_active = true;

    public function mount(PriceList $priceList): void
    {
        $this->priceList = $priceList;
        $this->name = $priceList->name ?? '';
        $this->code = $priceList->code ?? '';
        $this->currency = $priceList->currency ?? '';
        $this->valid_from = $priceList->valid_from?->format('Y-m-d') ?? '';
        $this->valid_until = $priceList->valid_until?->format('Y-m-d') ?? '';
        $this->is_active = $priceList->is_active;
    }

    public function save(): void
    {
        $validated = $this->validate($this->priceListUpdateRules($this->priceList->id));

        $this->priceList->update($validated);

        Flux::toast(variant: 'success', text: __('Price lists updated successfully.'));

        $this->redirect(route('price-lists.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Price lists') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="currency" :label="__('Currency')" />
        <flux:input wire:model="valid_from" type="date" :label="__('Valid From')" />
        <flux:input wire:model="valid_until" type="date" :label="__('Valid Until')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('price-lists.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
