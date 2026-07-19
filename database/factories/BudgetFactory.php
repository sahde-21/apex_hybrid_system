<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Budget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'reference_number' => fake()->unique()->bothify('REF-######'),
            'period_start' => fake()->date(),
            'period_end' => fake()->date(),
            'allocated_amount' => fake()->randomFloat(2, 0, 10000),
            'spent_amount' => fake()->randomFloat(2, 0, 10000),
            'branch_id' => Branch::factory(),
            'notes' => fake()->paragraph(),
        ];
    }
}
