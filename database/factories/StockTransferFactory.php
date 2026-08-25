<?php

namespace Database\Factories;

use App\Enums\StockTransferStatus;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('REF-######'),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id' => Warehouse::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'transfer_date' => fake()->date(),
            'status' => StockTransferStatus::Draft,
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
