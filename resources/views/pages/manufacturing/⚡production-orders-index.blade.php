<?php

use App\Models\ProductionOrder;
use App\Enums\ProductionOrderStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Production orders')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $productionOrderToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function productionOrders()
    {
        return ProductionOrder::query()
            ->with(['product', 'warehouse', 'branch'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('status', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->productionOrderToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteProductionOrder(): void
    {
        if ($this->productionOrderToDelete === null) {
            return;
        }

        $model = ProductionOrder::query()->findOrFail($this->productionOrderToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->productionOrderToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Production orders deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Production orders')"
        :subtitle="__('Manage Production orders')"
    >
        <x-slot:actions>
            <flux:button :href="route('production-orders.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (ProductionOrderStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->productionOrders">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference Number') }}</flux:table.column>
                <flux:table.column>{{ __('Product Id') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Warehouse Id') }}</flux:table.column>
                <flux:table.column>{{ __('Branch Id') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->productionOrders as $productionOrder)
                    <flux:table.row wire:key="production-orders-{{ $productionOrder->id }}">
                        <flux:table.cell>{{ $productionOrder->reference_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $productionOrder->product?->name ?? $productionOrder->product?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$productionOrder->status->color()">{{ $productionOrder->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $productionOrder->warehouse?->name ?? $productionOrder->warehouse?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $productionOrder->branch?->name ?? $productionOrder->branch?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('production-orders.edit', $productionOrder)" wire:navigate />
                                @can('delete', $productionOrder)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $productionOrder->id }})" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="inbox" :title="__('No records found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure? This action cannot be undone.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="deleteProductionOrder">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
