<?php

use App\Concerns\ShippingMethodValidationRules;
use App\Models\ShippingMethod;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Shipping methods')] class extends Component {
    use ShippingMethodValidationRules;
    public ShippingMethod $shippingMethod;

    public string $name = '';
    public string $code = '';
    public string $carrier = '';
    public string $base_cost = '0';
    public bool $is_active = true;

    public function mount(ShippingMethod $shippingMethod): void
    {
        $this->shippingMethod = $shippingMethod;
        $this->name = $shippingMethod->name ?? '';
        $this->code = $shippingMethod->code ?? '';
        $this->carrier = $shippingMethod->carrier ?? '';
        $this->base_cost = (string) $shippingMethod->base_cost;
        $this->is_active = $shippingMethod->is_active;
    }

    public function save(): void
    {
        $validated = $this->validate($this->shippingMethodUpdateRules($this->shippingMethod->id));

        $this->shippingMethod->update($validated);

        Flux::toast(variant: 'success', text: __('Shipping methods updated successfully.'));

        $this->redirect(route('shipping-methods.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Shipping methods') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="carrier" :label="__('Carrier')" />
        <flux:input wire:model="base_cost" type="number" step="0.01" :label="__('Base Cost')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('shipping-methods.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
