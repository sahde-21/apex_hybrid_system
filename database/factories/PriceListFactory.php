<?php

namespace Database\Factories;

use App\Models\PriceList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceList>
 */
class PriceListFactory extends Factory
{
    protected $model = PriceList::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => fake()->unique()->bothify('??-####'),
            'currency' => fake()->word(),
            'valid_from' => fake()->date(),
            'valid_until' => fake()->date(),
            'is_active' => fake()->boolean(),
        ];
    }
}
