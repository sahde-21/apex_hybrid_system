<?php

use App\Models\StockTransfer;
use App\Enums\StockTransferStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Stock transfers')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $stockTransferToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function stockTransfers()
    {
        return StockTransfer::query()
            ->with(['product', 'fromWarehouse', 'toWarehouse'])
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
        $this->stockTransferToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteStockTransfer(): void
    {
        if ($this->stockTransferToDelete === null) {
            return;
        }

        $model = StockTransfer::query()->findOrFail($this->stockTransferToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->stockTransferToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Stock transfers deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Stock transfers')"
        :subtitle="__('Manage Stock transfers')"
    >
        <x-slot:actions>
            <flux:button :href="route('stock-transfers.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (StockTransferStatus::workflowOptions() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->stockTransfers">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference Number') }}</flux:table.column>
                <flux:table.column>{{ __('Product Id') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('From Warehouse Id') }}</flux:table.column>
                <flux:table.column>{{ __('To Warehouse Id') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->stockTransfers as $stockTransfer)
                    <flux:table.row wire:key="stock-transfers-{{ $stockTransfer->id }}">
                        <flux:table.cell>{{ $stockTransfer->reference_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $stockTransfer->product?->name ?? $stockTransfer->product?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$stockTransfer->status->color()">{{ $stockTransfer->status->workflowLabel() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $stockTransfer->fromWarehouse?->name ?? $stockTransfer->fromWarehouse?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $stockTransfer->toWarehouse?->name ?? $stockTransfer->toWarehouse?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('stock-transfers.edit', $stockTransfer)" wire:navigate />
                                @can('delete', $stockTransfer)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $stockTransfer->id }})" />
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
                <flux:button variant="danger" wire:click="deleteStockTransfer">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
