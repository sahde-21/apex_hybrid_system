<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'branch_id' => Branch::factory(),
            'start_time' => '09:00:00',
            'end_time' => '09:00:00',
            'is_active' => fake()->boolean(),
        ];
    }
}
