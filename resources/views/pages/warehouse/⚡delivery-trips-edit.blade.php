<?php

use App\Models\DeliveryTrip;
use App\Enums\DeliveryTripStatus;
use App\Models\ShippingMethod;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Edit Delivery trips')] class extends \App\Livewire\ConcernBases\DeliveryTripValidationRulesBase {
    public DeliveryTrip $deliveryTrip;

    public string $reference_number = '';
    public ?int $shipping_method_id = null;
    public string $driver_name = '';
    public string $vehicle_plate = '';
    public string $trip_date = '';
    public string $status = 'planned';
    public string $notes = '';

    public function mount(DeliveryTrip $deliveryTrip): void
    {
        $this->deliveryTrip = $deliveryTrip;
        $this->reference_number = $deliveryTrip->reference_number ?? '';
        $this->shipping_method_id = $deliveryTrip->shipping_method_id;
        $this->driver_name = $deliveryTrip->driver_name ?? '';
        $this->vehicle_plate = $deliveryTrip->vehicle_plate ?? '';
        $this->trip_date = $deliveryTrip->trip_date?->format('Y-m-d') ?? '';
        $this->status = $deliveryTrip->status->value;
        $this->notes = $deliveryTrip->notes ?? '';
    }

    #[Computed]
    public function shippingMethods()
    {
        return \App\Models\ShippingMethod::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->deliveryTripUpdateRules($this->deliveryTrip->id));

        $this->deliveryTrip->update($validated);

        Flux::toast(variant: 'success', text: __('Delivery trips updated successfully.'));

        $this->redirect(route('delivery-trips.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Delivery trips') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference Number')" required />
        <flux:select wire:model="shipping_method_id" :label="__('Shipping Method Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->shippingMethods as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="driver_name" :label="__('Driver Name')" />
        <flux:input wire:model="vehicle_plate" :label="__('Vehicle Plate')" />
        <flux:input wire:model="trip_date" type="date" :label="__('Trip Date')" required />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (DeliveryTripStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('delivery-trips.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
