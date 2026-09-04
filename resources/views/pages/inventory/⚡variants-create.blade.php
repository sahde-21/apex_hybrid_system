<?php

use App\Models\Variant;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create Variants')] class extends \App\Livewire\ConcernBases\VariantValidationRulesBase {
    public ?int $product_id = null;
    public string $name = '';
    public string $sku = '';
    public string $barcode = '';
    public string $sale_price = '0';
    public string $purchase_price = '0';
    public int $stock_quantity = 0;
    public bool $is_active = true;

    public function mount(): void
    {
    }

    #[Computed]
    public function products()
    {
        return \App\Models\Product::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->variantRules());

        Variant::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Variants created successfully.'));

        $this->redirect(route('variants.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Variants') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:select wire:model="product_id" :label="__('Product Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->products as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="sku" :label="__('Sku')" required />
        <flux:input wire:model="barcode" :label="__('Barcode')" />
        <flux:input wire:model="sale_price" type="number" step="0.01" :label="__('Sale Price')" />
        <flux:input wire:model="purchase_price" type="number" step="0.01" :label="__('Purchase Price')" />
        <flux:input wire:model="stock_quantity" type="number" :label="__('Stock Quantity')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('variants.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
