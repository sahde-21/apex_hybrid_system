<?php

namespace Database\Factories;

use App\Enums\BillStatus;
use App\Models\Bill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    protected $model = Bill::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 5000);
        $tax = round($subtotal * 0.1, 2);

        return [
            'reference_number' => fake()->unique()->bothify('BILL-####-??'),
            'contact_id' => null,
            'purchase_order_id' => null,
            'bill_date' => fake()->date(),
            'due_date' => fake()->optional()->date(),
            'status' => BillStatus::Draft,
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => $tax,
            'total_amount' => $subtotal + $tax,
            'paid_amount' => 0,
            'currency_code' => 'IQD',
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
