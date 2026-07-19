<?php

namespace Database\Factories;

use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxRate>
 */
class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'code' => fake()->unique()->bothify('TAX-??##'),
            'rate' => fake()->randomFloat(2, 0, 100),
            'is_active' => fake()->boolean(),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}