<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 1, 500);
        $salePrice = $purchasePrice + fake()->randomFloat(2, 1, 100);

        return [
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'description' => fake()->optional()->sentence(),
            'purchase_price' => $purchasePrice,
            'sale_price' => $salePrice,
            'stock_quantity' => fake()->numberBetween(0, 500),
            'minimum_stock_level' => fake()->numberBetween(5, 50),
            'is_active' => true,
            'is_favorite' => false,
            'barcode' => fake()->optional()->ean13(),
            'category_id' => null,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $minimumStockLevel = $attributes['minimum_stock_level'] ?? 10;

            return [
                'stock_quantity' => fake()->numberBetween(0, $minimumStockLevel),
                'minimum_stock_level' => $minimumStockLevel,
            ];
        });
    }
}
