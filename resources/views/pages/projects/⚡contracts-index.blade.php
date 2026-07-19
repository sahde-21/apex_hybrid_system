<?php

use App\Models\Contract;
use App\Enums\ContractStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Contracts')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $contractToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function contracts()
    {
        return Contract::query()
            ->with(['contact'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('title', 'like', "%{$this->search}%")
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
        $this->contractToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteContract(): void
    {
        if ($this->contractToDelete === null) {
            return;
        }

        $model = Contract::query()->findOrFail($this->contractToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->contractToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Contracts deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Contracts')"
        :subtitle="__('Manage Contracts')"
    >
        <x-slot:actions>
            <flux:button :href="route('contracts.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (ContractStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->contracts">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference Number') }}</flux:table.column>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Contact Id') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->contracts as $contract)
                    <flux:table.row wire:key="contracts-{{ $contract->id }}">
                        <flux:table.cell>{{ $contract->reference_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $contract->title ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $contract->contact?->name ?? $contract->contact?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$contract->status->color()">{{ $contract->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('contracts.edit', $contract)" wire:navigate />
                                @can('delete', $contract)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $contract->id }})" />
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
                <flux:button variant="danger" wire:click="deleteContract">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
