<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\FloorPlan;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FloorPlan>
 */
class FloorPlanFactory extends Factory
{
    protected $model = FloorPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'warehouse_id' => Warehouse::factory(),
            'branch_id' => Branch::factory(),
            'width' => fake()->numberBetween(1, 100),
            'height' => fake()->numberBetween(1, 100),
            'layout_data' => [],
            'is_active' => fake()->boolean(),
        ];
    }
}
