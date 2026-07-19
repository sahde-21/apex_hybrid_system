<?php

use App\Models\Branch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Branches')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $branchToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function branches()
    {
        return Branch::query()
            
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
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

    public function confirmDelete(int $id): void
    {
        $this->branchToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteBranch(): void
    {
        if ($this->branchToDelete === null) {
            return;
        }

        $model = Branch::query()->findOrFail($this->branchToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->branchToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Branches deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Branches')"
        :subtitle="__('Manage Branches')"
    >
        <x-slot:actions>
            <flux:button :href="route('branches.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->branches">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Address') }}</flux:table.column>
                <flux:table.column>{{ __('Phone') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->branches as $branch)
                    <flux:table.row wire:key="branches-{{ $branch->id }}">
                        <flux:table.cell>{{ $branch->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $branch->code ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $branch->address ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $branch->phone ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $branch->email ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('branches.edit', $branch)" wire:navigate />
                                @can('delete', $branch)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $branch->id }})" />
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
                <flux:button variant="danger" wire:click="deleteBranch">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
