<?php

namespace Database\Factories;

use App\Enums\RfqStatus;
use App\Models\Rfq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rfq>
 */
class RfqFactory extends Factory
{
    protected $model = Rfq::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 5000);
        $tax = round($subtotal * 0.1, 2);

        return [
            'reference_number' => fake()->unique()->bothify('RFQ-####-??'),
            'purchase_request_id' => null,
            'rfq_date' => fake()->date(),
            'valid_until' => fake()->optional()->date(),
            'status' => RfqStatus::Draft,
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => $tax,
            'total_amount' => $subtotal + $tax,
            'currency_code' => 'IQD',
            'notes' => fake()->optional()->paragraph(),
            'terms' => null,
            'selected_vendor_id' => null,
            'converted_purchase_order_id' => null,
            'converted_at' => null,
        ];
    }
}
