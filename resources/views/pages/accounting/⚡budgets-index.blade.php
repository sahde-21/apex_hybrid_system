<?php

use App\Models\Budget;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Budgeting')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $budgetToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function budgets()
    {
        return Budget::query()
            ->with(['branch'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
                        ->orWhere('reference_number', 'like', "%{$this->search}%")
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
        $this->budgetToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteBudget(): void
    {
        if ($this->budgetToDelete === null) {
            return;
        }

        $model = Budget::query()->findOrFail($this->budgetToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->budgetToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Budgeting deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Budgeting')"
        :subtitle="__('Manage Budgeting')"
    >
        <x-slot:actions>
            <flux:button :href="route('budgets.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->budgets">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference Number') }}</flux:table.column>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Period Start') }}</flux:table.column>
                <flux:table.column>{{ __('Period End') }}</flux:table.column>
                <flux:table.column>{{ __('Allocated Amount') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->budgets as $budget)
                    <flux:table.row wire:key="budgets-{{ $budget->id }}">
                        <flux:table.cell>{{ $budget->reference_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $budget->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $budget->period_start?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $budget->period_end?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $budget->allocated_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('budgets.edit', $budget)" wire:navigate />
                                @can('delete', $budget)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $budget->id }})" />
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
                <flux:button variant="danger" wire:click="deleteBudget">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
