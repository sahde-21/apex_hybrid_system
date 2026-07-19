<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->word(),
            'source' => fake()->word(),
            'status' => fake()->randomElement(array_column(LeadStatus::cases(), 'value')),
            'estimated_value' => fake()->randomFloat(2, 0, 10000),
            'notes' => fake()->paragraph(),
        ];
    }
}
