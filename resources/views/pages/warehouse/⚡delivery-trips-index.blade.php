<?php

use App\Models\DeliveryTrip;
use App\Enums\DeliveryTripStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Delivery trips')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $deliveryTripToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function deliveryTrips()
    {
        return DeliveryTrip::query()
            ->with(['shippingMethod'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('driver_name', 'like', "%{$this->search}%")
                        ->orWhere('vehicle_plate', 'like', "%{$this->search}%")
                        ->orWhere('status', 'like', "%{$this->search}%");
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
        $this->deliveryTripToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteDeliveryTrip(): void
    {
        if ($this->deliveryTripToDelete === null) {
            return;
        }

        $model = DeliveryTrip::query()->findOrFail($this->deliveryTripToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->deliveryTripToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Delivery trips deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Delivery trips')"
        :subtitle="__('Manage Delivery trips')"
    >
        <x-slot:actions>
            <flux:button :href="route('delivery-trips.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (DeliveryTripStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->deliveryTrips">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference Number') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Shipping Method Id') }}</flux:table.column>
                <flux:table.column>{{ __('Driver Name') }}</flux:table.column>
                <flux:table.column>{{ __('Vehicle Plate') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->deliveryTrips as $deliveryTrip)
                    <flux:table.row wire:key="delivery-trips-{{ $deliveryTrip->id }}">
                        <flux:table.cell>{{ $deliveryTrip->reference_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$deliveryTrip->status->color()">{{ $deliveryTrip->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $deliveryTrip->shippingMethod?->name ?? $deliveryTrip->shippingMethod?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $deliveryTrip->driver_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $deliveryTrip->vehicle_plate ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('delivery-trips.edit', $deliveryTrip)" wire:navigate />
                                @can('delete', $deliveryTrip)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $deliveryTrip->id }})" />
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
                <flux:button variant="danger" wire:click="deleteDeliveryTrip">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
