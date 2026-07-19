<?php

use App\Models\InventoryAdjustment;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Inventory adjustments')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $inventoryAdjustmentToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, InventoryAdjustment>
     */
    #[Computed]
    public function inventoryAdjustments()
    {
        return InventoryAdjustment::query()
            ->with(['product', 'warehouse'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('reason', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%")
                        ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('warehouse', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->latest('adjustment_date')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $inventoryAdjustmentId): void
    {
        $this->inventoryAdjustmentToDelete = $inventoryAdjustmentId;
        $this->showDeleteModal = true;
    }

    public function deleteInventoryAdjustment(): void
    {
        if ($this->inventoryAdjustmentToDelete === null) {
            return;
        }

        $model = InventoryAdjustment::query()->findOrFail($this->inventoryAdjustmentToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->inventoryAdjustmentToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Inventory adjustment deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Inventory adjustments')"
        :subtitle="__('Track stock corrections and quantity changes')"
    >
        <x-slot:actions>
            <flux:button :href="route('inventory-adjustments.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add inventory adjustment') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, product, warehouse, reason, or notes...')" />
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->inventoryAdjustments">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Product') }}</flux:table.column>
                <flux:table.column>{{ __('Warehouse') }}</flux:table.column>
                <flux:table.column>{{ __('Adjustment date') }}</flux:table.column>
                <flux:table.column>{{ __('Reason') }}</flux:table.column>
                <flux:table.column>{{ __('Quantity change') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->inventoryAdjustments as $inventoryAdjustment)
                    <flux:table.row wire:key="inventory-adjustment-{{ $inventoryAdjustment->id }}">
                        <flux:table.cell class="font-medium">{{ $inventoryAdjustment->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $inventoryAdjustment->product?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $inventoryAdjustment->warehouse?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $inventoryAdjustment->adjustment_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $inventoryAdjustment->reason }}</flux:table.cell>
                        <flux:table.cell>{{ $inventoryAdjustment->quantity_change }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                
@can('update', $inventoryAdjustment)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('inventory-adjustments.edit', $inventoryAdjustment)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $inventoryAdjustment)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $inventoryAdjustment->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No inventory adjustments found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete inventory adjustment') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this inventory adjustment? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteInventoryAdjustment">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
