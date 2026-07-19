<?php

namespace Database\Factories;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('QT-####-??'),
            'contact_id' => null,
            'quotation_date' => fake()->date(),
            'valid_until' => fake()->optional()->date(),
            'status' => QuotationStatus::Draft,
            'subtotal_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => fake()->randomFloat(2, 0, 10000),
            'currency_code' => 'IQD',
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
