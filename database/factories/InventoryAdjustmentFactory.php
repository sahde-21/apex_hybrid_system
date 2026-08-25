<?php

namespace Database\Factories;

use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryAdjustmentStatus;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Warehouse;
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
            'warehouse_id' => Warehouse::factory(),
            'variant_id' => null,
            'adjustment_date' => fake()->date(),
            'quantity_change' => fake()->randomElement([-5, -3, -1, 1, 2, 5, 10]),
            'reason' => fake()->randomElement(array_column(InventoryAdjustmentReason::cases(), 'value')),
            'notes' => fake()->optional()->paragraph(),
            'status' => InventoryAdjustmentStatus::Draft,
        ];
    }
}
