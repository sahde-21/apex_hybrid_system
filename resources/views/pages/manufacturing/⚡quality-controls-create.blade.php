<?php

use App\Models\QualityControl;
use App\Enums\QualityControlStatus;
use App\Models\ProductionOrder;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create Quality control')] class extends \App\Livewire\ConcernBases\QualityControlValidationRulesBase {
    public string $reference_number = '';
    public ?int $production_order_id = null;
    public ?int $product_id = null;
    public string $inspection_date = '';
    public string $status = 'pending';
    public int $passed_quantity = 0;
    public int $failed_quantity = 0;
    public string $notes = '';

    public function mount(): void
    {
        $this->inspection_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function productionOrders()
    {
        return \App\Models\ProductionOrder::query()->orderBy('name')->get();
    }

    #[Computed]
    public function products()
    {
        return \App\Models\Product::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->qualityControlRules());

        QualityControl::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Quality control created successfully.'));

        $this->redirect(route('quality-controls.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Quality control') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference Number')" required />
        <flux:select wire:model="production_order_id" :label="__('Production Order Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->productionOrders as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="product_id" :label="__('Product Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->products as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="inspection_date" type="date" :label="__('Inspection Date')" required />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (QualityControlStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="passed_quantity" type="number" :label="__('Passed Quantity')" />
        <flux:input wire:model="failed_quantity" type="number" :label="__('Failed Quantity')" />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('quality-controls.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
