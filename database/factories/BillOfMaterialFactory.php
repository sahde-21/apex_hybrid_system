<?php

namespace Database\Factories;

use App\Models\BillOfMaterial;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillOfMaterial>
 */
class BillOfMaterialFactory extends Factory
{
    protected $model = BillOfMaterial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'component_product_id' => Product::factory(),
            'quantity' => fake()->randomFloat(2, 0, 10000),
            'unit' => 'pcs',
            'notes' => fake()->paragraph(),
        ];
    }
}
