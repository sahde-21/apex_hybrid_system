<?php

use App\Models\PriceList;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Price lists')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $priceListToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function priceLists()
    {
        return PriceList::query()
            
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('currency', 'like', "%{$this->search}%");
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
        $this->priceListToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deletePriceList(): void
    {
        if ($this->priceListToDelete === null) {
            return;
        }

        $model = PriceList::query()->findOrFail($this->priceListToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->priceListToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Price lists deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Price lists')"
        :subtitle="__('Manage Price lists')"
    >
        <x-slot:actions>
            <flux:button :href="route('price-lists.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->priceLists">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Currency') }}</flux:table.column>
                <flux:table.column>{{ __('Valid From') }}</flux:table.column>
                <flux:table.column>{{ __('Valid Until') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->priceLists as $priceList)
                    <flux:table.row wire:key="price-lists-{{ $priceList->id }}">
                        <flux:table.cell>{{ $priceList->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $priceList->code ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $priceList->currency ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $priceList->valid_from?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $priceList->valid_until?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('price-lists.edit', $priceList)" wire:navigate />
                                @can('delete', $priceList)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $priceList->id }})" />
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
                <flux:button variant="danger" wire:click="deletePriceList">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
