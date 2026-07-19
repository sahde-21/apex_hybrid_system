<?php

use App\Models\Expense;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Expenses')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $expenseToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Expense>
     */
    #[Computed]
    public function expenses()
    {
        return Expense::query()
            ->with('contact')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('category', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('contact', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->latest('expense_date')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $expenseId): void
    {
        $this->expenseToDelete = $expenseId;
        $this->showDeleteModal = true;
    }

    public function deleteExpense(): void
    {
        if ($this->expenseToDelete === null) {
            return;
        }

        $model = Expense::query()->findOrFail($this->expenseToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->expenseToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Expense deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Expenses')"
        :subtitle="__('Record and track business expenses')"
    >
        <x-slot:actions>
            <flux:button :href="route('expenses.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add expense') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, category, description, or contact...')" />
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->expenses">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Category') }}</flux:table.column>
                <flux:table.column>{{ __('Description') }}</flux:table.column>
                <flux:table.column>{{ __('Contact') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Amount') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->expenses as $expense)
                    <flux:table.row wire:key="expense-{{ $expense->id }}">
                        <flux:table.cell class="font-medium">{{ $expense->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $expense->category }}</flux:table.cell>
                        <flux:table.cell>{{ $expense->description }}</flux:table.cell>
                        <flux:table.cell>{{ $expense->contact?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $expense->expense_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $expense->amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <x-print-button type="expense" :id="$expense->id" />
                                
@can('update', $expense)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('expenses.edit', $expense)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $expense)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $expense->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No expenses found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete expense') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this expense? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteExpense">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
