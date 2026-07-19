<?php

use App\Concerns\VehicleMaintenanceValidationRules;
use App\Models\VehicleMaintenance;
use App\Enums\VehicleMaintenanceStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Vehicle maintenance')] class extends Component {
    use VehicleMaintenanceValidationRules;
    public VehicleMaintenance $vehicleMaintenance;

    public string $vehicle_plate = '';
    public string $maintenance_date = '';
    public string $maintenance_type = '';
    public string $cost = '0';
    public string $status = 'scheduled';
    public string $notes = '';

    public function mount(VehicleMaintenance $vehicleMaintenance): void
    {
        $this->vehicleMaintenance = $vehicleMaintenance;
        $this->vehicle_plate = $vehicleMaintenance->vehicle_plate ?? '';
        $this->maintenance_date = $vehicleMaintenance->maintenance_date?->format('Y-m-d') ?? '';
        $this->maintenance_type = $vehicleMaintenance->maintenance_type ?? '';
        $this->cost = (string) $vehicleMaintenance->cost;
        $this->status = $vehicleMaintenance->status->value;
        $this->notes = $vehicleMaintenance->notes ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate($this->vehicleMaintenanceUpdateRules($this->vehicleMaintenance->id));

        $this->vehicleMaintenance->update($validated);

        Flux::toast(variant: 'success', text: __('Vehicle maintenance updated successfully.'));

        $this->redirect(route('vehicle-maintenance.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Vehicle maintenance') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="vehicle_plate" :label="__('Vehicle Plate')" required />
        <flux:input wire:model="maintenance_date" type="date" :label="__('Maintenance Date')" required />
        <flux:input wire:model="maintenance_type" :label="__('Maintenance Type')" required />
        <flux:input wire:model="cost" type="number" step="0.01" :label="__('Cost')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (VehicleMaintenanceStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('vehicle-maintenance.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
