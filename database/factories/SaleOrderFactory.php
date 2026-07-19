<?php

namespace Database\Factories;

use App\Enums\SaleOrderStatus;
use App\Models\SaleOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleOrder>
 */
class SaleOrderFactory extends Factory
{
    protected $model = SaleOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('SO-####-??'),
            'contact_id' => null,
            'warehouse_id' => null,
            'order_date' => fake()->date(),
            'delivery_date' => fake()->optional()->date(),
            'status' => SaleOrderStatus::Draft,
            'subtotal_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => fake()->randomFloat(2, 0, 10000),
            'currency_code' => 'IQD',
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
