<?php

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Quotations')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $quotationToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Quotation>
     */
    #[Computed]
    public function quotations()
    {
        return Quotation::query()
            ->with('contact')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%")
                        ->orWhereHas('contact', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest('quotation_date')
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

    public function confirmDelete(int $quotationId): void
    {
        $this->quotationToDelete = $quotationId;
        $this->showDeleteModal = true;
    }

    public function deleteQuotation(): void
    {
        if ($this->quotationToDelete === null) {
            return;
        }

        $model = Quotation::query()->findOrFail($this->quotationToDelete);

        if (! $model->status->isEditable()) {
            $this->quotationToDelete = null;
            $this->showDeleteModal = false;
            Flux::toast(variant: 'danger', text: __('scf.sales_workflow.immutable_posted'));

            return;
        }

        $this->authorize('delete', $model);

        $model->delete();

        $this->quotationToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Quotation deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Quotations')"
        :subtitle="__('Create and manage customer price quotes')"
    >
        <x-slot:actions>
            <flux:button :href="route('quotations.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add quotation') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, customer, or notes...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (QuotationStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->quotations">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Quotation date') }}</flux:table.column>
                <flux:table.column>{{ __('Valid until') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->quotations as $quotation)
                    <flux:table.row wire:key="quotation-{{ $quotation->id }}">
                        <flux:table.cell class="font-medium">
                            <a href="{{ route('quotations.show', $quotation) }}" wire:navigate class="hover:underline">
                                {{ $quotation->reference_number }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $quotation->contact?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $quotation->quotation_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $quotation->valid_until?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$quotation->status->color()">{{ $quotation->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $quotation->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="eye"
                                    :href="route('quotations.show', $quotation)"
                                    wire:navigate
                                />
                                <x-print-button type="quotation" :id="$quotation->id" />
                                
@can('update', $quotation)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('quotations.edit', $quotation)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $quotation)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $quotation->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No quotations found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete quotation') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this quotation? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteQuotation">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
