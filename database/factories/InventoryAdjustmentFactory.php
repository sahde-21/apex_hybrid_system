<?php

namespace Database\Factories;

use App\Models\InventoryAdjustment;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryAdjustment>
 */
class InventoryAdjustmentFactory extends Factory
{
    protected $model = InventoryAdjustment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('IA-####-??'),
            'product_id' => Product::factory(),
            'warehouse_id' => null,
            'adjustment_date' => fake()->date(),
            'quantity_change' => fake()->numberBetween(-50, 50),
            'reason' => fake()->sentence(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}