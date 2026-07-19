<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Invoices')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $invoiceToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Invoice>
     */
    #[Computed]
    public function invoices()
    {
        return Invoice::query()
            ->with('contact')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%")
                        ->orWhereHas('contact', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest('invoice_date')
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

    public function confirmDelete(int $invoiceId): void
    {
        $this->invoiceToDelete = $invoiceId;
        $this->showDeleteModal = true;
    }

    public function deleteInvoice(): void
    {
        if ($this->invoiceToDelete === null) {
            return;
        }

        $model = Invoice::query()->findOrFail($this->invoiceToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->invoiceToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Invoice deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Invoices')"
        :subtitle="__('Manage customer billing and receivables')"
    />

    <x-module-toolbar
        export-type="invoices"
        create-permission="invoices.create"
        :create-route="route('invoices.create')"
        :create-label="__('Add invoice')"
    >
        <x-slot:search>
            <flux:input class="min-w-64 flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, customer, or notes...')" />
        </x-slot:search>
        <x-slot:filters>
            <flux:select class="min-w-40" wire:model.live="status" :placeholder="__('All statuses')">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                @foreach (InvoiceStatus::options() as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </x-slot:filters>
    </x-module-toolbar>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->invoices">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Invoice date') }}</flux:table.column>
                <flux:table.column>{{ __('Due date') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->invoices as $invoice)
                    <flux:table.row wire:key="invoice-{{ $invoice->id }}">
                        <flux:table.cell class="font-medium">{{ $invoice->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->contact?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->invoice_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$invoice->status->color()">{{ $invoice->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $invoice->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <x-print-button type="invoice" :id="$invoice->id" />
                                
@can('update', $invoice)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('invoices.edit', $invoice)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $invoice)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $invoice->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No invoices found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete invoice') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this invoice? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteInvoice">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
