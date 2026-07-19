<?php

use App\Concerns\ProductValidationRules;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create product')] class extends Component {
    use ProductValidationRules;

    public string $name = '';
    public string $sku = '';
    public string $description = '';
    public string $purchase_price = '';
    public string $sale_price = '';
    public int $stock_quantity = 0;
    public int $minimum_stock_level = 0;

    public function save(): void
    {
        $validated = $this->validate($this->productRules());

        Product::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Product created successfully.'));

        $this->redirect(route('products.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create product') }}</flux:heading>
        <flux:subheading>{{ __('Add a new product to your inventory') }}</flux:subheading>
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
            <flux:button type="submit" variant="primary">{{ __('Create product') }}</flux:button>
            <flux:button :href="route('products.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
