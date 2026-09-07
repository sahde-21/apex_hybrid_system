<?php

use App\Models\BillOfMaterial;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Edit Bill of materials')] class extends \App\Livewire\ConcernBases\BillOfMaterialValidationRulesBase {
    public BillOfMaterial $billOfMaterial;

    public ?int $product_id = null;
    public ?int $component_product_id = null;
    public string $quantity = '0';
    public string $unit = 'pcs';
    public string $notes = '';

    public function mount(BillOfMaterial $billOfMaterial): void
    {
        $this->billOfMaterial = $billOfMaterial;
        $this->product_id = $billOfMaterial->product_id;
        $this->component_product_id = $billOfMaterial->component_product_id;
        $this->quantity = (string) $billOfMaterial->quantity;
        $this->unit = $billOfMaterial->unit ?? '';
        $this->notes = $billOfMaterial->notes ?? '';
    }

    #[Computed]
    public function products()
    {
        return \App\Models\Product::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->billOfMaterialUpdateRules($this->billOfMaterial->id));

        $this->billOfMaterial->update($validated);

        Flux::toast(variant: 'success', text: __('Bill of materials updated successfully.'));

        $this->redirect(route('bill-of-materials.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Bill of materials') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:select wire:model="product_id" :label="__('Product Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->products as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="component_product_id" :label="__('Component Product Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->products as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="quantity" type="number" step="0.01" :label="__('Quantity')" required />
        <flux:input wire:model="unit" :label="__('Unit')" />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('bill-of-materials.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
