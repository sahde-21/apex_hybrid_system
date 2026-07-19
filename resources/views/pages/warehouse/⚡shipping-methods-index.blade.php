<?php

use App\Models\ShippingMethod;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Shipping methods')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $shippingMethodToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function shippingMethods()
    {
        return ShippingMethod::query()
            
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('carrier', 'like', "%{$this->search}%");
                });
            })
            
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->shippingMethodToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteShippingMethod(): void
    {
        if ($this->shippingMethodToDelete === null) {
            return;
        }

        $model = ShippingMethod::query()->findOrFail($this->shippingMethodToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->shippingMethodToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Shipping methods deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Shipping methods')"
        :subtitle="__('Manage Shipping methods')"
    >
        <x-slot:actions>
            <flux:button :href="route('shipping-methods.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->shippingMethods">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Carrier') }}</flux:table.column>
                <flux:table.column>{{ __('Base Cost') }}</flux:table.column>
                <flux:table.column>{{ __('Is Active') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->shippingMethods as $shippingMethod)
                    <flux:table.row wire:key="shipping-methods-{{ $shippingMethod->id }}">
                        <flux:table.cell>{{ $shippingMethod->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $shippingMethod->code ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $shippingMethod->carrier ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $shippingMethod->base_cost, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ $shippingMethod->is_active ? __('Yes') : __('No') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('shipping-methods.edit', $shippingMethod)" wire:navigate />
                                @can('delete', $shippingMethod)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $shippingMethod->id }})" />
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
                <flux:button variant="danger" wire:click="deleteShippingMethod">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
