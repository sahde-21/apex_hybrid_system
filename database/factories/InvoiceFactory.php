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
        return [
            'reference_number' => fake()->unique()->bothify('INV-####-??'),
            'contact_id' => null,
            'sale_order_id' => null,
            'invoice_date' => fake()->date(),
            'due_date' => fake()->optional()->date(),
            'status' => fake()->randomElement(InvoiceStatus::cases()),
            'subtotal_amount' => fake()->randomFloat(2, 0, 10000),
            'total_amount' => fake()->randomFloat(2, 0, 10000),
            'tax_amount' => fake()->randomFloat(2, 0, 1000),
            'discount_amount' => 0,
            'notes' => fake()->optional()->paragraph(),
            'source' => 'manual',
        ];
    }
}