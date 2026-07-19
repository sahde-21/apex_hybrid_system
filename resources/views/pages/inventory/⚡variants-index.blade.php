<?php

use App\Models\Variant;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Variants')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $variantToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function variants()
    {
        return Variant::query()
            ->with(['product'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%")
                        ->orWhere('barcode', 'like', "%{$this->search}%");
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
        $this->variantToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteVariant(): void
    {
        if ($this->variantToDelete === null) {
            return;
        }

        $model = Variant::query()->findOrFail($this->variantToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->variantToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Variants deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Variants')"
        :subtitle="__('Manage Variants')"
    >
        <x-slot:actions>
            <flux:button :href="route('variants.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->variants">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Product Id') }}</flux:table.column>
                <flux:table.column>{{ __('Sku') }}</flux:table.column>
                <flux:table.column>{{ __('Barcode') }}</flux:table.column>
                <flux:table.column>{{ __('Sale Price') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->variants as $variant)
                    <flux:table.row wire:key="variants-{{ $variant->id }}">
                        <flux:table.cell>{{ $variant->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $variant->product?->name ?? $variant->product?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $variant->sku ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $variant->barcode ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $variant->sale_price, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('variants.edit', $variant)" wire:navigate />
                                @can('delete', $variant)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $variant->id }})" />
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
                <flux:button variant="danger" wire:click="deleteVariant">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
