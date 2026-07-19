<?php

namespace App\Concerns;

use App\Enums\DeliveryTripStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait DeliveryTripValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function deliveryTripRules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('delivery_trips', 'reference_number')],
            'shipping_method_id' => ['nullable', 'exists:shipping_methods,id'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:255'],
            'trip_date' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(DeliveryTripStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function deliveryTripUpdateRules(?int $deliveryTripId = null): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('delivery_trips', 'reference_number')->ignore($deliveryTripId)],
            'shipping_method_id' => ['nullable', 'exists:shipping_methods,id'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:255'],
            'trip_date' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(DeliveryTripStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
