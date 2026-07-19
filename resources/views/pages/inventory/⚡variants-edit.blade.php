<?php

use App\Concerns\VariantValidationRules;
use App\Models\Variant;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Variants')] class extends Component {
    use VariantValidationRules;
    public Variant $variant;

    public ?int $product_id = null;
    public string $name = '';
    public string $sku = '';
    public string $barcode = '';
    public string $sale_price = '0';
    public string $purchase_price = '0';
    public int $stock_quantity = 0;
    public bool $is_active = true;

    public function mount(Variant $variant): void
    {
        $this->variant = $variant;
        $this->product_id = $variant->product_id;
        $this->name = $variant->name ?? '';
        $this->sku = $variant->sku ?? '';
        $this->barcode = $variant->barcode ?? '';
        $this->sale_price = (string) $variant->sale_price;
        $this->purchase_price = (string) $variant->purchase_price;
        $this->stock_quantity = (string) $variant->stock_quantity;
        $this->is_active = $variant->is_active;
    }

    #[Computed]
    public function products()
    {
        return \App\Models\Product::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->variantUpdateRules($this->variant->id));

        $this->variant->update($validated);

        Flux::toast(variant: 'success', text: __('Variants updated successfully.'));

        $this->redirect(route('variants.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Variants') }}</flux:heading>
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
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('variants.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
