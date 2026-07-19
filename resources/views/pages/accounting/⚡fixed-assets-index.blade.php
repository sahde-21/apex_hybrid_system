<?php

use App\Models\FixedAsset;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Fixed assets')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $fixedAssetToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function fixedAssets()
    {
        return FixedAsset::query()
            ->with(['branch'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
                        ->orWhere('asset_code', 'like', "%{$this->search}%")
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
        $this->fixedAssetToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteFixedAsset(): void
    {
        if ($this->fixedAssetToDelete === null) {
            return;
        }

        $model = FixedAsset::query()->findOrFail($this->fixedAssetToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->fixedAssetToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Fixed assets deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Fixed assets')"
        :subtitle="__('Manage Fixed assets')"
    >
        <x-slot:actions>
            <flux:button :href="route('fixed-assets.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->fixedAssets">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Asset Code') }}</flux:table.column>
                <flux:table.column>{{ __('Purchase Date') }}</flux:table.column>
                <flux:table.column>{{ __('Purchase Cost') }}</flux:table.column>
                <flux:table.column>{{ __('Current Value') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->fixedAssets as $fixedAsset)
                    <flux:table.row wire:key="fixed-assets-{{ $fixedAsset->id }}">
                        <flux:table.cell>{{ $fixedAsset->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $fixedAsset->asset_code ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $fixedAsset->purchase_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $fixedAsset->purchase_cost, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $fixedAsset->current_value, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('fixed-assets.edit', $fixedAsset)" wire:navigate />
                                @can('delete', $fixedAsset)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $fixedAsset->id }})" />
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
                <flux:button variant="danger" wire:click="deleteFixedAsset">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
