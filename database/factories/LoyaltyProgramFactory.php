<?php

namespace Database\Factories;

use App\Models\LoyaltyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyProgram>
 */
class LoyaltyProgramFactory extends Factory
{
    protected $model = LoyaltyProgram::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => fake()->unique()->bothify('??-####'),
            'points_per_currency' => fake()->randomFloat(2, 0, 10000),
            'is_active' => fake()->boolean(),
            'description' => fake()->paragraph(),
        ];
    }
}
