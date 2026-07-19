<?php

use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Warehouses')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $warehouseToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Warehouse>
     */
    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('address', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $warehouseId): void
    {
        $this->warehouseToDelete = $warehouseId;
        $this->showDeleteModal = true;
    }

    public function deleteWarehouse(): void
    {
        if ($this->warehouseToDelete === null) {
            return;
        }

        $model = Warehouse::query()->findOrFail($this->warehouseToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->warehouseToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Warehouse deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Warehouses')"
        :subtitle="__('Manage storage locations and inventory hubs')"
    >
        <x-slot:actions>
            <flux:button :href="route('warehouses.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add warehouse') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by name, code, address, or phone...')" />
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->warehouses">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Address') }}</flux:table.column>
                <flux:table.column>{{ __('Phone') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->warehouses as $warehouse)
                    <flux:table.row wire:key="warehouse-{{ $warehouse->id }}">
                        <flux:table.cell class="font-medium">{{ $warehouse->name }}</flux:table.cell>
                        <flux:table.cell>{{ $warehouse->code }}</flux:table.cell>
                        <flux:table.cell>{{ $warehouse->address ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $warehouse->phone ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$warehouse->is_active ? 'green' : 'zinc'">
                                {{ $warehouse->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                
@can('update', $warehouse)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('warehouses.edit', $warehouse)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $warehouse)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $warehouse->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="inbox" :title="__('No warehouses found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete warehouse') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this warehouse? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteWarehouse">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
