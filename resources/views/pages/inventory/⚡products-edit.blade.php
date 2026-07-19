<?php

use App\Concerns\ProductValidationRules;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit product')] class extends Component {
    use ProductValidationRules;

    public Product $product;

    public string $name = '';
    public string $sku = '';
    public string $description = '';
    public string $purchase_price = '';
    public string $sale_price = '';
    public int $stock_quantity = 0;
    public int $minimum_stock_level = 0;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->description = $product->description ?? '';
        $this->purchase_price = (string) $product->purchase_price;
        $this->sale_price = (string) $product->sale_price;
        $this->stock_quantity = $product->stock_quantity;
        $this->minimum_stock_level = $product->minimum_stock_level;
    }

    public function save(): void
    {
        $validated = $this->validate($this->productRules($this->product->id));

        $this->product->update($validated);

        Flux::toast(variant: 'success', text: __('Product updated successfully.'));

        $this->redirect(route('products.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit product') }}</flux:heading>
        <flux:subheading>{{ __('Update product and inventory details') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6 md:grid-cols-2">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="sku" :label="__('SKU')" required />
        <div class="md:col-span-2">
            <flux:textarea wire:model="description" :label="__('Description')" />
        </div>
        <flux:input wire:model="purchase_price" type="number" step="0.01" :label="__('Purchase price')" required />
        <flux:input wire:model="sale_price" type="number" step="0.01" :label="__('Sale price')" required />
        <flux:input wire:model="stock_quantity" type="number" :label="__('Stock quantity')" required />
        <flux:input wire:model="minimum_stock_level" type="number" :label="__('Minimum stock level')" required />

        <div class="flex gap-2 md:col-span-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('products.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
