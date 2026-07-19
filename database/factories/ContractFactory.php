<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\Contact;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('REF-######'),
            'contact_id' => Contact::factory(),
            'title' => fake()->words(3, true),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'value' => fake()->randomFloat(2, 0, 10000),
            'status' => fake()->randomElement(array_column(ContractStatus::cases(), 'value')),
            'notes' => fake()->paragraph(),
        ];
    }
}
