<?php

use App\Enums\SaleOrderStatus;
use App\Models\SaleOrder;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Sale orders')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $saleOrderToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, SaleOrder>
     */
    #[Computed]
    public function saleOrders()
    {
        return SaleOrder::query()
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

    public function confirmDelete(int $saleOrderId): void
    {
        $this->saleOrderToDelete = $saleOrderId;
        $this->showDeleteModal = true;
    }

    public function deleteSaleOrder(): void
    {
        if ($this->saleOrderToDelete === null) {
            return;
        }

        $model = SaleOrder::query()->findOrFail($this->saleOrderToDelete);

        if (! $model->status->isEditable()) {
            $this->saleOrderToDelete = null;
            $this->showDeleteModal = false;
            Flux::toast(variant: 'danger', text: __('scf.sales_workflow.immutable_posted'));

            return;
        }

        $this->authorize('delete', $model);

        $model->delete();

        $this->saleOrderToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Sale order deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Sale orders')"
        :subtitle="__('Manage customer orders and outgoing sales')"
    >
        <x-slot:actions>
            <flux:button :href="route('sale-orders.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add sale order') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-module-toolbar>
        <x-slot:search>
            <flux:input class="min-w-64 flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, customer, or notes...')" />
        </x-slot:search>
        <x-slot:filters>
            <flux:select class="min-w-40" wire:model.live="status" :placeholder="__('All statuses')">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                @foreach (SaleOrderStatus::options() as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </x-slot:filters>
    </x-module-toolbar>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->saleOrders">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Warehouse') }}</flux:table.column>
                <flux:table.column>{{ __('Order date') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->saleOrders as $saleOrder)
                    <flux:table.row wire:key="sale-order-{{ $saleOrder->id }}">
                        <flux:table.cell class="font-medium">
                            <a href="{{ route('sale-orders.show', $saleOrder) }}" wire:navigate class="hover:underline">
                                {{ $saleOrder->reference_number }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $saleOrder->contact?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $saleOrder->warehouse?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $saleOrder->order_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$saleOrder->status->color()">{{ $saleOrder->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $saleOrder->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="eye"
                                    :href="route('sale-orders.show', $saleOrder)"
                                    wire:navigate
                                />
                                <x-print-button type="sale-order" :id="$saleOrder->id" />
                                
@can('update', $saleOrder)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('sale-orders.edit', $saleOrder)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $saleOrder)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $saleOrder->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No sale orders found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete sale order') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this sale order? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteSaleOrder">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
