<?php

use App\Enums\BillStatus;
use App\Models\Bill;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Bills')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $billToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Bill>
     */
    #[Computed]
    public function bills()
    {
        return Bill::query()
            ->with('contact')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%")
                        ->orWhereHas('contact', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest('bill_date')
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

    public function confirmDelete(int $billId): void
    {
        $model = Bill::query()->findOrFail($billId);
        if (! $model->status->isEditable()) {
            Flux::toast(variant: 'danger', text: __('scf.purchase_workflow.immutable_posted'));
            return;
        }
        $this->billToDelete = $billId;
        $this->showDeleteModal = true;
    }

    public function deleteBill(): void
    {
        if ($this->billToDelete === null) {
            return;
        }

        $model = Bill::query()->findOrFail($this->billToDelete);
        $this->authorize('delete', $model);
        $model->delete();

        $this->billToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Bill deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Bills')"
        :subtitle="__('Manage supplier bills and payables')"
    >
        <x-slot:actions>
            <flux:button :href="route('bills.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add bill') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, supplier, or notes...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (BillStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->bills">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Supplier') }}</flux:table.column>
                <flux:table.column>{{ __('Bill date') }}</flux:table.column>
                <flux:table.column>{{ __('Due date') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->bills as $bill)
                    <flux:table.row wire:key="bill-{{ $bill->id }}">
                        <flux:table.cell class="font-medium">
                            @if (Route::has('bills.show'))
                                <a href="{{ route('bills.show', $bill) }}" wire:navigate class="hover:underline">
                                    {{ $bill->reference_number }}
                                </a>
                            @else
                                {{ $bill->reference_number }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $bill->contact?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $bill->bill_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $bill->due_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$bill->status->color()">{{ $bill->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $bill->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <x-print-button type="bill" :id="$bill->id" />
                                @if (Route::has('bills.show'))
                                    <flux:button size="sm" variant="ghost" icon="eye"
                                        :href="route('bills.show', $bill)" wire:navigate />
                                @endif
                                @can('update', $bill)
                                    @if ($bill->status->isEditable())
                                        <flux:button size="sm" variant="ghost" icon="pencil-square"
                                            :href="route('bills.edit', $bill)" wire:navigate />
                                    @endif
                                @endcan
                                @can('delete', $bill)
                                    <flux:button size="sm" variant="ghost" icon="trash"
                                        wire:click="confirmDelete({{ $bill->id }})" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No bills found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete bill') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this bill? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteBill">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
