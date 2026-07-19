<?php

use App\Concerns\ShippingMethodValidationRules;
use App\Models\ShippingMethod;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Shipping methods')] class extends Component {
    use ShippingMethodValidationRules;
    public string $name = '';
    public string $code = '';
    public string $carrier = '';
    public string $base_cost = '0';
    public bool $is_active = true;

    public function mount(): void
    {
    }

    public function save(): void
    {
        $validated = $this->validate($this->shippingMethodRules());

        ShippingMethod::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Shipping methods created successfully.'));

        $this->redirect(route('shipping-methods.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Shipping methods') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="carrier" :label="__('Carrier')" />
        <flux:input wire:model="base_cost" type="number" step="0.01" :label="__('Base Cost')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('shipping-methods.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
