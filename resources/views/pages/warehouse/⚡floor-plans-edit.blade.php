<?php

use App\Models\FloorPlan;
use App\Models\Warehouse;
use App\Models\Branch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Edit Floor plans')] class extends \App\Livewire\ConcernBases\FloorPlanValidationRulesBase {
    public FloorPlan $floorPlan;

    public string $name = '';
    public ?int $warehouse_id = null;
    public ?int $branch_id = null;
    public int $width = 0;
    public int $height = 0;
    public string $layout_data = '';
    public bool $is_active = true;

    public function mount(FloorPlan $floorPlan): void
    {
        $this->floorPlan = $floorPlan;
        $this->name = $floorPlan->name ?? '';
        $this->warehouse_id = $floorPlan->warehouse_id;
        $this->branch_id = $floorPlan->branch_id;
        $this->width = (string) $floorPlan->width;
        $this->height = (string) $floorPlan->height;
        $this->layout_data = json_encode($floorPlan->layout_data ?? []);
        $this->is_active = $floorPlan->is_active;
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
        $validated = $this->validate($this->floorPlanUpdateRules($this->floorPlan->id));

        $this->floorPlan->update($validated);

        Flux::toast(variant: 'success', text: __('Floor plans updated successfully.'));

        $this->redirect(route('floor-plans.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Floor plans') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
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
        <flux:input wire:model="width" type="number" :label="__('Width')" />
        <flux:input wire:model="height" type="number" :label="__('Height')" />
        <flux:textarea wire:model="layout_data" :label="__('Layout Data')" :placeholder="__('JSON')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('floor-plans.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
