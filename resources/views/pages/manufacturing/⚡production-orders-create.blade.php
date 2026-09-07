<?php

use App\Models\ProductionOrder;
use App\Enums\ProductionOrderStatus;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Branch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create Production orders')] class extends \App\Livewire\ConcernBases\ProductionOrderValidationRulesBase {
    public string $reference_number = '';
    public ?int $product_id = null;
    public ?int $warehouse_id = null;
    public ?int $branch_id = null;
    public int $quantity = 0;
    public string $start_date = '';
    public string $end_date = '';
    public string $status = 'draft';
    public string $notes = '';

    public function mount(): void
    {
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function products()
    {
        return \App\Models\Product::query()->orderBy('name')->get();
    }

    #[Computed]
    public function warehouses()
    {
        return \App\Models\Warehouse::query()->orderBy('name')->get();
    }

    #[Computed]
    public function branches()
    {
        return \App\Models\Branch::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->productionOrderRules());

        ProductionOrder::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Production orders created successfully.'));

        $this->redirect(route('production-orders.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Production orders') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference Number')" required />
        <flux:select wire:model="product_id" :label="__('Product Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->products as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="warehouse_id" :label="__('Warehouse Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->warehouses as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="branch_id" :label="__('Branch Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->branches as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="quantity" type="number" :label="__('Quantity')" required />
        <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
        <flux:input wire:model="end_date" type="date" :label="__('End Date')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (ProductionOrderStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('production-orders.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
