<?php

namespace App\Http\Requests;

use App\Enums\VehicleMaintenanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('vehicle-maintenance.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'vehicle_plate' => ['required', 'string', 'max:255'],
            'maintenance_date' => ['required', 'date'],
            'maintenance_type' => ['required', 'string', 'max:255'],
            'cost' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(VehicleMaintenanceStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
