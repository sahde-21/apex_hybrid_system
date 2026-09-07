<?php

use App\Enums\InventoryAdjustmentReason;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryAdjustmentWorkflowService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Edit inventory adjustment')] class extends \App\Livewire\ConcernBases\InventoryAdjustmentValidationRulesBase {

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
        $this->authorize('view', $inventoryAdjustment);
        $this->inventoryAdjustment = $inventoryAdjustment->fresh() ?? $inventoryAdjustment;
        $this->syncForm();
    }

    protected function syncForm(): void
    {
        $adjustment = $this->inventoryAdjustment;
        $this->reference_number = $adjustment->reference_number;
        $this->product_id = $adjustment->product_id;
        $this->warehouse_id = $adjustment->warehouse_id;
        $this->adjustment_date = $adjustment->adjustment_date->format('Y-m-d');
        $this->quantity_change = $adjustment->quantity_change;
        $this->reason = $adjustment->reason;
        $this->notes = $adjustment->notes ?? '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    #[Computed]
    public function products()
    {
        return Product::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Warehouse>
     */
    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function save(InventoryAdjustmentWorkflowService $workflow): void
    {
        $this->authorize('update', $this->inventoryAdjustment);
        $validated = $this->validate($this->inventoryAdjustmentRules($this->inventoryAdjustment->id));
        $this->inventoryAdjustment = $workflow->updateDraft($this->inventoryAdjustment, $validated, Auth::user());
        Flux::toast(variant: 'success', text: __('Inventory adjustment updated successfully.'));
        $this->redirect(route('inventory-adjustments.index'), navigate: true);
    }

    public function approve(InventoryAdjustmentWorkflowService $workflow): void
    {
        $this->authorize('approve', $this->inventoryAdjustment);
        try {
            $this->inventoryAdjustment = $workflow->approve($this->inventoryAdjustment, Auth::user());
            $this->syncForm();
            Flux::toast(variant: 'success', text: __('Adjustment approved.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function post(InventoryAdjustmentWorkflowService $workflow): void
    {
        $this->authorize('post', $this->inventoryAdjustment);
        try {
            $this->inventoryAdjustment = $workflow->post($this->inventoryAdjustment, Auth::user());
            $this->syncForm();
            Flux::toast(variant: 'success', text: __('Adjustment posted.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function cancelAdjustment(InventoryAdjustmentWorkflowService $workflow): void
    {
        $this->authorize('cancel', $this->inventoryAdjustment);
        try {
            $this->inventoryAdjustment = $workflow->cancel($this->inventoryAdjustment, Auth::user());
            $this->syncForm();
            Flux::toast(variant: 'success', text: __('Adjustment cancelled.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit inventory adjustment') }}</flux:heading>
        <flux:subheading>
            {{ __('Status') }}: {{ $inventoryAdjustment->status->label() }}
        </flux:subheading>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @can('approve', $inventoryAdjustment)
            <flux:button wire:click="approve" variant="primary">{{ __('Approve') }}</flux:button>
        @endcan
        @can('post', $inventoryAdjustment)
            <flux:button wire:click="post" variant="primary">{{ __('Post') }}</flux:button>
        @endcan
        @can('cancel', $inventoryAdjustment)
            <flux:button wire:click="cancelAdjustment" variant="danger">{{ __('Cancel adjustment') }}</flux:button>
        @endcan
    </div>

    @if ($inventoryAdjustment->status->isEditable())
        <form wire:submit="save" class="grid max-w-2xl gap-6">
            <flux:input wire:model="reference_number" :label="__('Reference number')" required />
            <flux:select wire:model="product_id" :label="__('Product')" :placeholder="__('Select product')" required>
                @foreach ($this->products as $product)
                    <flux:select.option :value="$product->id">{{ $product->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="warehouse_id" :label="__('Warehouse')" :placeholder="__('Select warehouse')" required>
                @foreach ($this->warehouses as $warehouse)
                    <flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="adjustment_date" type="date" :label="__('Adjustment date')" required />
            <flux:input wire:model="quantity_change" type="number" step="1" :label="__('Quantity change')" required />
            <flux:select wire:model="reason" :label="__('Reason')" required>
                @foreach (InventoryAdjustmentReason::options() as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea wire:model="notes" :label="__('Notes')" />

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('Save draft') }}</flux:button>
                <flux:button :href="route('inventory-adjustments.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
            </div>
        </form>
    @else
        <div class="grid max-w-2xl gap-2 text-sm text-zinc-600 dark:text-zinc-300">
            <p>{{ __('Reference') }}: {{ $inventoryAdjustment->reference_number }}</p>
            <p>{{ __('Quantity change') }}: {{ $inventoryAdjustment->quantity_change }}</p>
            <p>{{ __('Reason') }}: {{ $inventoryAdjustment->reasonEnum()?->label() ?? $inventoryAdjustment->reason }}</p>
            <p>{{ __('Notes') }}: {{ $inventoryAdjustment->notes }}</p>
            <flux:button :href="route('inventory-adjustments.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    @endif
</section>
