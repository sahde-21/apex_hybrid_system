<?php

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Purchase requests')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $prToDelete = null;
    public bool $showDeleteModal = false;

    /** @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, PurchaseRequest> */
    #[Computed]
    public function purchaseRequests()
    {
        return PurchaseRequest::query()
            ->with('requester')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('department', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest('request_date')
            ->paginate(10);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    public function confirmDelete(int $id): void
    {
        $pr = PurchaseRequest::query()->findOrFail($id);
        if (! $pr->status->isEditable()) {
            Flux::toast(variant: 'danger', text: __('scf.purchase_workflow.immutable_posted'));
            return;
        }
        $this->prToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deletePurchaseRequest(): void
    {
        if ($this->prToDelete === null) return;
        $model = PurchaseRequest::query()->findOrFail($this->prToDelete);
        $this->authorize('delete', $model);
        $model->delete();
        $this->prToDelete = null;
        $this->showDeleteModal = false;
        Flux::toast(variant: 'success', text: __('Purchase request deleted.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header :title="__('Purchase requests')" :subtitle="__('Manage internal purchase requests')">
        <x-slot:actions>
            @can('purchase-requests.create')
                <flux:button :href="route('purchase-requests.create')" icon="plus" variant="primary" wire:navigate>
                    {{ __('New request') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, department...')" />
        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (PurchaseRequestStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->purchaseRequests">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Department') }}</flux:table.column>
                <flux:table.column>{{ __('Request date') }}</flux:table.column>
                <flux:table.column>{{ __('Needed by') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->purchaseRequests as $pr)
                    <flux:table.row wire:key="pr-{{ $pr->id }}">
                        <flux:table.cell class="font-medium">
                            @if (Route::has('purchase-requests.show'))
                                <a href="{{ route('purchase-requests.show', $pr) }}" wire:navigate class="hover:underline">
                                    {{ $pr->reference_number }}
                                </a>
                            @else
                                {{ $pr->reference_number }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $pr->department ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $pr->request_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $pr->needed_by?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$pr->status->color()">{{ $pr->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $pr->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @if (Route::has('purchase-requests.show'))
                                    <flux:button size="sm" variant="ghost" icon="eye"
                                        :href="route('purchase-requests.show', $pr)" wire:navigate />
                                @endif
                                @can('update', $pr)
                                    @if ($pr->status->isEditable())
                                        <flux:button size="sm" variant="ghost" icon="pencil-square"
                                            :href="route('purchase-requests.edit', $pr)" wire:navigate />
                                    @endif
                                @endcan
                                @can('delete', $pr)
                                    <flux:button size="sm" variant="ghost" icon="trash"
                                        wire:click="confirmDelete({{ $pr->id }})" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No purchase requests found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete purchase request') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure? This cannot be undone.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deletePurchaseRequest">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
