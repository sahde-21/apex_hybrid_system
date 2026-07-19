<?php

use App\Concerns\VehicleMaintenanceValidationRules;
use App\Models\VehicleMaintenance;
use App\Enums\VehicleMaintenanceStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Vehicle maintenance')] class extends Component {
    use VehicleMaintenanceValidationRules;
    public string $vehicle_plate = '';
    public string $maintenance_date = '';
    public string $maintenance_type = '';
    public string $cost = '0';
    public string $status = 'scheduled';
    public string $notes = '';

    public function mount(): void
    {
        $this->maintenance_date = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $validated = $this->validate($this->vehicleMaintenanceRules());

        VehicleMaintenance::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Vehicle maintenance created successfully.'));

        $this->redirect(route('vehicle-maintenance.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Vehicle maintenance') }}</flux:heading>
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
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('vehicle-maintenance.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
