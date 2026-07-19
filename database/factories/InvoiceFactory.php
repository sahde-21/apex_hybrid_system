<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 5000);
        $tax = round($subtotal * 0.1, 2);

        return [
            'reference_number' => fake()->unique()->bothify('INV-####-??'),
            'contact_id' => null,
            'sale_order_id' => null,
            'invoice_date' => fake()->date(),
            'due_date' => fake()->optional()->date(),
            'status' => InvoiceStatus::Draft,
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal + $tax,
            'tax_amount' => $tax,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'currency_code' => 'IQD',
            'notes' => fake()->optional()->paragraph(),
            'source' => 'manual',
        ];
    }
}
