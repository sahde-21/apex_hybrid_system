<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Variant;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockLevel>
 */
class StockLevelFactory extends Factory
{
    protected $model = StockLevel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'on_hand' => 0,
            'reserved' => 0,
            'average_cost' => null,
            'minimum_level' => null,
            'version' => 0,
        ];
    }

    public function forVariant(?Variant $variant = null): static
    {
        return $this->state(function () use ($variant) {
            $variant ??= Variant::factory()->create(['is_active' => true]);

            return [
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
            ];
        });
    }
}
