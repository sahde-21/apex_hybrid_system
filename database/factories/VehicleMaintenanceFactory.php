<?php

namespace Database\Factories;

use App\Enums\VehicleMaintenanceStatus;
use App\Models\VehicleMaintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleMaintenance>
 */
class VehicleMaintenanceFactory extends Factory
{
    protected $model = VehicleMaintenance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_plate' => fake()->word(),
            'maintenance_date' => fake()->date(),
            'maintenance_type' => 'general',
            'cost' => fake()->randomFloat(2, 0, 10000),
            'status' => fake()->randomElement(array_column(VehicleMaintenanceStatus::cases(), 'value')),
            'notes' => fake()->paragraph(),
        ];
    }
}
