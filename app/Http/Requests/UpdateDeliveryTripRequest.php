<?php

namespace App\Http\Requests;

use App\Enums\DeliveryTripStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delivery-trips.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('deliveryTrip')?->id;

        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('delivery_trips', 'reference_number')->ignore($id)],
            'shipping_method_id' => ['nullable', 'exists:shipping_methods,id'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:255'],
            'trip_date' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(DeliveryTripStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
