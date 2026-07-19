<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\FixedAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedAsset>
 */
class FixedAssetFactory extends Factory
{
    protected $model = FixedAsset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'asset_code' => fake()->unique()->bothify('??-####'),
            'purchase_date' => fake()->date(),
            'purchase_cost' => fake()->randomFloat(2, 0, 10000),
            'current_value' => fake()->randomFloat(2, 0, 10000),
            'branch_id' => Branch::factory(),
            'notes' => fake()->paragraph(),
        ];
    }
}
