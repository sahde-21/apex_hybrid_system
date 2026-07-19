<?php

use App\Models\LoyaltyProgram;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Loyalty programs')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $loyaltyProgramToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function loyaltyPrograms()
    {
        return LoyaltyProgram::query()
            
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
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
        $this->loyaltyProgramToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteLoyaltyProgram(): void
    {
        if ($this->loyaltyProgramToDelete === null) {
            return;
        }

        $model = LoyaltyProgram::query()->findOrFail($this->loyaltyProgramToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->loyaltyProgramToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Loyalty programs deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Loyalty programs')"
        :subtitle="__('Manage Loyalty programs')"
    >
        <x-slot:actions>
            <flux:button :href="route('loyalty-programs.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->loyaltyPrograms">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Points Per Currency') }}</flux:table.column>
                <flux:table.column>{{ __('Is Active') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->loyaltyPrograms as $loyaltyProgram)
                    <flux:table.row wire:key="loyalty-programs-{{ $loyaltyProgram->id }}">
                        <flux:table.cell>{{ $loyaltyProgram->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $loyaltyProgram->code ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $loyaltyProgram->points_per_currency, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ $loyaltyProgram->is_active ? __('Yes') : __('No') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('loyalty-programs.edit', $loyaltyProgram)" wire:navigate />
                                @can('delete', $loyaltyProgram)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $loyaltyProgram->id }})" />
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
                <flux:button variant="danger" wire:click="deleteLoyaltyProgram">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
