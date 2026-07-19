<?php

use App\Concerns\InventoryAdjustmentValidationRules;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit inventory adjustment')] class extends Component {
    use InventoryAdjustmentValidationRules;

    public InventoryAdjustment $inventoryAdjustment;

    public string $reference_number = '';
    public ?int $product_id = null;
    public ?int $warehouse_id = null;
    public string $adjustment_date = '';
    public int $quantity_change = 0;
    public string $reason = '';
    public string $notes = '';

    public function mount(InventoryAdjustment $inventoryAdjustment): void
    {
        $this->inventoryAdjustment = $inventoryAdjustment;
        $this->reference_number = $inventoryAdjustment->reference_number;
        $this->product_id = $inventoryAdjustment->product_id;
        $this->warehouse_id = $inventoryAdjustment->warehouse_id;
        $this->adjustment_date = $inventoryAdjustment->adjustment_date->format('Y-m-d');
        $this->quantity_change = $inventoryAdjustment->quantity_change;
        $this->reason = $inventoryAdjustment->reason;
        $this->notes = $inventoryAdjustment->notes ?? '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    #[Computed]
    public function products()
    {
        return Product::query()->orderBy('name')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Warehouse>
     */
    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->inventoryAdjustmentRules($this->inventoryAdjustment->id));

        $this->inventoryAdjustment->update($validated);

        Flux::toast(variant: 'success', text: __('Inventory adjustment updated successfully.'));

        $this->redirect(route('inventory-adjustments.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit inventory adjustment') }}</flux:heading>
        <flux:subheading>{{ __('Update inventory adjustment details') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:select wire:model="product_id" :label="__('Product')" :placeholder="__('Select product')" required>
            @foreach ($this->products as $product)
                <flux:select.option :value="$product->id">{{ $product->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="warehouse_id" :label="__('Warehouse')" :placeholder="__('Select warehouse')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->warehouses as $warehouse)
                <flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="adjustment_date" type="date" :label="__('Adjustment date')" required />
        <flux:input wire:model="quantity_change" type="number" step="1" :label="__('Quantity change')" required />
        <flux:input wire:model="reason" :label="__('Reason')" required />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('inventory-adjustments.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
