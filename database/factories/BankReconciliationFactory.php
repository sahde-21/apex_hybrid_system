<?php

namespace Database\Factories;

use App\Enums\BankReconciliationStatus;
use App\Models\BankReconciliation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankReconciliation>
 */
class BankReconciliationFactory extends Factory
{
    protected $model = BankReconciliation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('REF-######'),
            'bank_name' => fake()->word(),
            'statement_date' => fake()->date(),
            'opening_balance' => fake()->randomFloat(2, 0, 10000),
            'closing_balance' => fake()->randomFloat(2, 0, 10000),
            'status' => fake()->randomElement(array_column(BankReconciliationStatus::cases(), 'value')),
            'notes' => fake()->paragraph(),
        ];
    }
}
