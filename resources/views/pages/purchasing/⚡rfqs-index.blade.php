<?php

use App\Enums\RfqStatus;
use App\Models\Rfq;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('RFQs')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $rfqToDelete = null;
    public bool $showDeleteModal = false;

    /** @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Rfq> */
    #[Computed]
    public function rfqs()
    {
        return Rfq::query()
            ->with(['selectedVendor', 'purchaseRequest'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest('rfq_date')
            ->paginate(10);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    public function confirmDelete(int $id): void
    {
        $rfq = Rfq::query()->findOrFail($id);
        if (! $rfq->status->isEditable()) {
            Flux::toast(variant: 'danger', text: __('scf.purchase_workflow.immutable_posted'));
            return;
        }
        $this->rfqToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteRfq(): void
    {
        if ($this->rfqToDelete === null) return;
        $model = Rfq::query()->findOrFail($this->rfqToDelete);
        $this->authorize('delete', $model);
        $model->delete();
        $this->rfqToDelete = null;
        $this->showDeleteModal = false;
        Flux::toast(variant: 'success', text: __('RFQ deleted.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header :title="__('Requests for Quotation')" :subtitle="__('Manage vendor RFQs and quotation comparisons')">
        <x-slot:actions>
            @can('rfqs.create')
                <flux:button :href="route('rfqs.create')" icon="plus" variant="primary" wire:navigate>
                    {{ __('New RFQ') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference...')" />
        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (RfqStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->rfqs">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('RFQ date') }}</flux:table.column>
                <flux:table.column>{{ __('Valid until') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->rfqs as $rfq)
                    <flux:table.row wire:key="rfq-{{ $rfq->id }}">
                        <flux:table.cell class="font-medium">
                            @if (Route::has('rfqs.show'))
                                <a href="{{ route('rfqs.show', $rfq) }}" wire:navigate class="hover:underline">
                                    {{ $rfq->reference_number }}
                                </a>
                            @else
                                {{ $rfq->reference_number }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $rfq->rfq_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $rfq->valid_until?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$rfq->status->color()">{{ $rfq->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $rfq->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @if (Route::has('rfqs.show'))
                                    <flux:button size="sm" variant="ghost" icon="eye"
                                        :href="route('rfqs.show', $rfq)" wire:navigate />
                                @endif
                                @can('update', $rfq)
                                    @if ($rfq->status->isEditable())
                                        <flux:button size="sm" variant="ghost" icon="pencil-square"
                                            :href="route('rfqs.edit', $rfq)" wire:navigate />
                                    @endif
                                @endcan
                                @can('delete', $rfq)
                                    <flux:button size="sm" variant="ghost" icon="trash"
                                        wire:click="confirmDelete({{ $rfq->id }})" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="inbox" :title="__('No RFQs found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete RFQ') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure? This cannot be undone.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="deleteRfq">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
