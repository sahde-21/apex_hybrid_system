<?php

namespace Database\Factories;

use App\Enums\ProductionOrderStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionOrder>
 */
class ProductionOrderFactory extends Factory
{
    protected $model = ProductionOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('REF-######'),
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'branch_id' => Branch::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'status' => fake()->randomElement(array_column(ProductionOrderStatus::cases(), 'value')),
            'notes' => fake()->paragraph(),
        ];
    }
}
