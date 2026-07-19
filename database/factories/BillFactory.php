<?php

namespace Database\Factories;

use App\Enums\BillStatus;
use App\Models\Bill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    protected $model = Bill::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('BILL-####-??'),
            'contact_id' => null,
            'bill_date' => fake()->date(),
            'due_date' => fake()->optional()->date(),
            'status' => fake()->randomElement(BillStatus::cases()),
            'total_amount' => fake()->randomFloat(2, 0, 10000),
            'tax_amount' => fake()->randomFloat(2, 0, 1000),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}