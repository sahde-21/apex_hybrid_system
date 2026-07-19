<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('PO-####-??'),
            'contact_id' => null,
            'warehouse_id' => null,
            'order_date' => fake()->date(),
            'expected_date' => fake()->optional()->date(),
            'status' => fake()->randomElement(PurchaseOrderStatus::cases()),
            'total_amount' => fake()->randomFloat(2, 0, 10000),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}