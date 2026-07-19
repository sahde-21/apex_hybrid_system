<?php

namespace App\Concerns;

use App\Enums\VehicleMaintenanceStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait VehicleMaintenanceValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function vehicleMaintenanceRules(): array
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

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function vehicleMaintenanceUpdateRules(?int $vehicleMaintenanceId = null): array
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
