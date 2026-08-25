<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 20);

        return [
            'uuid' => (string) Str::uuid(),
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity' => $quantity,
            'quantity_before' => 0,
            'quantity_after' => $quantity,
            'reserved_delta' => 0,
            'movement_type' => StockMovementType::Adjustment,
            'reason_code' => null,
            'occurred_at' => now(),
            'reference_type' => null,
            'reference_id' => null,
            'reference_line_id' => null,
            'idempotency_key' => 'factory-'.Str::uuid(),
            'unit_cost' => null,
            'notes' => null,
            'created_by' => User::factory(),
            'created_at' => now(),
        ];
    }
}
