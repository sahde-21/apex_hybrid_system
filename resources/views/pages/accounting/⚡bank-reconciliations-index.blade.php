<?php

use App\Models\BankReconciliation;
use App\Enums\BankReconciliationStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Bank reconciliation')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $bankReconciliationToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function bankReconciliations()
    {
        return BankReconciliation::query()
            
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('bank_name', 'like', "%{$this->search}%")
                        ->orWhere('status', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->bankReconciliationToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteBankReconciliation(): void
    {
        if ($this->bankReconciliationToDelete === null) {
            return;
        }

        $model = BankReconciliation::query()->findOrFail($this->bankReconciliationToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->bankReconciliationToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Bank reconciliation deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Bank reconciliation')"
        :subtitle="__('Manage Bank reconciliation')"
    >
        <x-slot:actions>
            <flux:button :href="route('bank-reconciliations.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (BankReconciliationStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->bankReconciliations">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference Number') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Bank Name') }}</flux:table.column>
                <flux:table.column>{{ __('Statement Date') }}</flux:table.column>
                <flux:table.column>{{ __('Opening Balance') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->bankReconciliations as $bankReconciliation)
                    <flux:table.row wire:key="bank-reconciliations-{{ $bankReconciliation->id }}">
                        <flux:table.cell>{{ $bankReconciliation->reference_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$bankReconciliation->status->color()">{{ $bankReconciliation->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $bankReconciliation->bank_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $bankReconciliation->statement_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $bankReconciliation->opening_balance, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('bank-reconciliations.edit', $bankReconciliation)" wire:navigate />
                                @can('delete', $bankReconciliation)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $bankReconciliation->id }})" />
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
                <flux:button variant="danger" wire:click="deleteBankReconciliation">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
