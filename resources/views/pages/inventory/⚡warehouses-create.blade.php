<?php

use App\Concerns\WarehouseValidationRules;
use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create warehouse')] class extends Component {
    use WarehouseValidationRules;

    public string $name = '';
    public string $code = '';
    public string $address = '';
    public string $phone = '';
    public bool $is_active = true;

    public function save(): void
    {
        $validated = $this->validate($this->warehouseRules());

        Warehouse::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Warehouse created successfully.'));

        $this->redirect(route('warehouses.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create warehouse') }}</flux:heading>
        <flux:subheading>{{ __('Add a new storage location') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:textarea wire:model="address" :label="__('Address')" />
        <flux:input wire:model="phone" :label="__('Phone')" />
        <flux:switch wire:model="is_active" :label="__('Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create warehouse') }}</flux:button>
            <flux:button :href="route('warehouses.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
