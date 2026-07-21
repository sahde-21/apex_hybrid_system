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
        $subtotal = fake()->randomFloat(2, 10, 5000);
        $tax = round($subtotal * 0.1, 2);

        return [
            'reference_number' => fake()->unique()->bothify('PO-####-??'),
            'contact_id' => null,
            'rfq_id' => null,
            'purchase_request_id' => null,
            'warehouse_id' => null,
            'branch_id' => null,
            'buyer_id' => null,
            'order_date' => fake()->date(),
            'expected_date' => fake()->optional()->date(),
            'status' => PurchaseOrderStatus::Draft,
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => $tax,
            'total_amount' => $subtotal + $tax,
            'currency_code' => 'IQD',
            'notes' => fake()->optional()->paragraph(),
            'terms' => null,
        ];
    }
}
