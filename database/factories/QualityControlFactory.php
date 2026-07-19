<?php

namespace Database\Factories;

use App\Enums\QualityControlStatus;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\QualityControl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityControl>
 */
class QualityControlFactory extends Factory
{
    protected $model = QualityControl::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('REF-######'),
            'production_order_id' => ProductionOrder::factory(),
            'product_id' => Product::factory(),
            'inspection_date' => fake()->date(),
            'status' => fake()->randomElement(array_column(QualityControlStatus::cases(), 'value')),
            'passed_quantity' => fake()->numberBetween(1, 100),
            'failed_quantity' => fake()->numberBetween(1, 100),
            'notes' => fake()->paragraph(),
        ];
    }
}
