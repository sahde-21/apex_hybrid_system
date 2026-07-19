<?php

use App\Models\SupplierEvaluation;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Supplier evaluations')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $supplierEvaluationToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function supplierEvaluations()
    {
        return SupplierEvaluation::query()
            ->with(['contact'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('comments', 'like', "%{$this->search}%")
                        ->orWhere('comments', 'like', "%{$this->search}%");
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
        $this->supplierEvaluationToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteSupplierEvaluation(): void
    {
        if ($this->supplierEvaluationToDelete === null) {
            return;
        }

        $model = SupplierEvaluation::query()->findOrFail($this->supplierEvaluationToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->supplierEvaluationToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Supplier evaluations deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Supplier evaluations')"
        :subtitle="__('Manage Supplier evaluations')"
    >
        <x-slot:actions>
            <flux:button :href="route('supplier-evaluations.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->supplierEvaluations">
            <flux:table.columns>
                <flux:table.column>{{ __('Contact Id') }}</flux:table.column>
                <flux:table.column>{{ __('Evaluation Date') }}</flux:table.column>
                <flux:table.column>{{ __('Quality Score') }}</flux:table.column>
                <flux:table.column>{{ __('Delivery Score') }}</flux:table.column>
                <flux:table.column>{{ __('Price Score') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->supplierEvaluations as $supplierEvaluation)
                    <flux:table.row wire:key="supplier-evaluations-{{ $supplierEvaluation->id }}">
                        <flux:table.cell>{{ $supplierEvaluation->contact?->name ?? $supplierEvaluation->contact?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $supplierEvaluation->evaluation_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $supplierEvaluation->quality_score ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $supplierEvaluation->delivery_score ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $supplierEvaluation->price_score ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('supplier-evaluations.edit', $supplierEvaluation)" wire:navigate />
                                @can('delete', $supplierEvaluation)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $supplierEvaluation->id }})" />
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
                <flux:button variant="danger" wire:click="deleteSupplierEvaluation">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
