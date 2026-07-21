<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Purchase orders')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $purchaseOrderToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, PurchaseOrder>
     */
    #[Computed]
    public function purchaseOrders()
    {
        return PurchaseOrder::query()
            ->with(['contact', 'warehouse'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%")
                        ->orWhereHas('contact', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest('order_date')
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

    public function confirmDelete(int $purchaseOrderId): void
    {
        $model = PurchaseOrder::query()->findOrFail($purchaseOrderId);
        if (! $model->status->isEditable()) {
            Flux::toast(variant: 'danger', text: __('scf.purchase_workflow.immutable_posted'));
            return;
        }
        $this->purchaseOrderToDelete = $purchaseOrderId;
        $this->showDeleteModal = true;
    }

    public function deletePurchaseOrder(): void
    {
        if ($this->purchaseOrderToDelete === null) {
            return;
        }

        $model = PurchaseOrder::query()->findOrFail($this->purchaseOrderToDelete);
        $this->authorize('delete', $model);
        $model->delete();

        $this->purchaseOrderToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Purchase order deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Purchase orders')"
        :subtitle="__('Track supplier orders and incoming inventory')"
    >
        <x-slot:actions>
            <flux:button :href="route('purchase-orders.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add purchase order') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, supplier, or notes...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (PurchaseOrderStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->purchaseOrders">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Supplier') }}</flux:table.column>
                <flux:table.column>{{ __('Warehouse') }}</flux:table.column>
                <flux:table.column>{{ __('Order date') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->purchaseOrders as $purchaseOrder)
                    <flux:table.row wire:key="purchase-order-{{ $purchaseOrder->id }}">
                        <flux:table.cell class="font-medium">
                            @if (Route::has('purchase-orders.show'))
                                <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" wire:navigate class="hover:underline">
                                    {{ $purchaseOrder->reference_number }}
                                </a>
                            @else
                                {{ $purchaseOrder->reference_number }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $purchaseOrder->contact?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $purchaseOrder->warehouse?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $purchaseOrder->order_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$purchaseOrder->status->color()">{{ $purchaseOrder->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $purchaseOrder->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <x-print-button type="purchase-order" :id="$purchaseOrder->id" />
                                @if (Route::has('purchase-orders.show'))
                                    <flux:button size="sm" variant="ghost" icon="eye"
                                        :href="route('purchase-orders.show', $purchaseOrder)" wire:navigate />
                                @endif
                                @can('update', $purchaseOrder)
                                    @if ($purchaseOrder->status->isEditable())
                                        <flux:button size="sm" variant="ghost" icon="pencil-square"
                                            :href="route('purchase-orders.edit', $purchaseOrder)" wire:navigate />
                                    @endif
                                @endcan
                                @can('delete', $purchaseOrder)
                                    <flux:button size="sm" variant="ghost" icon="trash"
                                        wire:click="confirmDelete({{ $purchaseOrder->id }})" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No purchase orders found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete purchase order') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this purchase order? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deletePurchaseOrder">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
