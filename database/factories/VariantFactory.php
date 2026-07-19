<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Variant>
 */
class VariantFactory extends Factory
{
    protected $model = Variant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('??-####'),
            'barcode' => fake()->word(),
            'sale_price' => fake()->randomFloat(2, 0, 10000),
            'purchase_price' => fake()->randomFloat(2, 0, 10000),
            'stock_quantity' => fake()->numberBetween(1, 100),
            'is_active' => fake()->boolean(),
        ];
    }
}
