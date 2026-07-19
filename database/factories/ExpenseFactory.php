<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('EXP-####-??'),
            'contact_id' => null,
            'category' => fake()->word(),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 0, 5000),
            'expense_date' => fake()->date(),
            'payment_method' => fake()->optional()->word(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}