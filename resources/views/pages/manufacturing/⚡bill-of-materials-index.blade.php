<?php

use App\Models\BillOfMaterial;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Bill of materials')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $billOfMaterialToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function billOfMaterials()
    {
        return BillOfMaterial::query()
            ->with(['product', 'componentProduct'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('unit', 'like', "%{$this->search}%")
                        ->orWhere('unit', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
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
        $this->billOfMaterialToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteBillOfMaterial(): void
    {
        if ($this->billOfMaterialToDelete === null) {
            return;
        }

        $model = BillOfMaterial::query()->findOrFail($this->billOfMaterialToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->billOfMaterialToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Bill of materials deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Bill of materials')"
        :subtitle="__('Manage Bill of materials')"
    >
        <x-slot:actions>
            <flux:button :href="route('bill-of-materials.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->billOfMaterials">
            <flux:table.columns>
                <flux:table.column>{{ __('Product Id') }}</flux:table.column>
                <flux:table.column>{{ __('Component Product Id') }}</flux:table.column>
                <flux:table.column>{{ __('Quantity') }}</flux:table.column>
                <flux:table.column>{{ __('Unit') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->billOfMaterials as $billOfMaterial)
                    <flux:table.row wire:key="bill-of-materials-{{ $billOfMaterial->id }}">
                        <flux:table.cell>{{ $billOfMaterial->product?->name ?? $billOfMaterial->product?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $billOfMaterial->componentProduct?->name ?? $billOfMaterial->componentProduct?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $billOfMaterial->quantity, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ $billOfMaterial->unit ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('bill-of-materials.edit', $billOfMaterial)" wire:navigate />
                                @can('delete', $billOfMaterial)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $billOfMaterial->id }})" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
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
                <flux:button variant="danger" wire:click="deleteBillOfMaterial">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
