<?php

use App\Models\VehicleMaintenance;
use App\Enums\VehicleMaintenanceStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Vehicle maintenance')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $vehicleMaintenanceToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function vehicleMaintenances()
    {
        return VehicleMaintenance::query()
            
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('vehicle_plate', 'like', "%{$this->search}%")
                        ->orWhere('vehicle_plate', 'like', "%{$this->search}%")
                        ->orWhere('maintenance_type', 'like', "%{$this->search}%")
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
        $this->vehicleMaintenanceToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteVehicleMaintenance(): void
    {
        if ($this->vehicleMaintenanceToDelete === null) {
            return;
        }

        $model = VehicleMaintenance::query()->findOrFail($this->vehicleMaintenanceToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->vehicleMaintenanceToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Vehicle maintenance deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Vehicle maintenance')"
        :subtitle="__('Manage Vehicle maintenance')"
    >
        <x-slot:actions>
            <flux:button :href="route('vehicle-maintenance.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (VehicleMaintenanceStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->vehicleMaintenances">
            <flux:table.columns>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Vehicle Plate') }}</flux:table.column>
                <flux:table.column>{{ __('Maintenance Date') }}</flux:table.column>
                <flux:table.column>{{ __('Maintenance Type') }}</flux:table.column>
                <flux:table.column>{{ __('Cost') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->vehicleMaintenances as $vehicleMaintenance)
                    <flux:table.row wire:key="vehicle-maintenance-{{ $vehicleMaintenance->id }}">
                        <flux:table.cell><flux:badge size="sm" :color="$vehicleMaintenance->status->color()">{{ $vehicleMaintenance->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $vehicleMaintenance->vehicle_plate ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $vehicleMaintenance->maintenance_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $vehicleMaintenance->maintenance_type ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $vehicleMaintenance->cost, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('vehicle-maintenance.edit', $vehicleMaintenance)" wire:navigate />
                                @can('delete', $vehicleMaintenance)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $vehicleMaintenance->id }})" />
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
                <flux:button variant="danger" wire:click="deleteVehicleMaintenance">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
