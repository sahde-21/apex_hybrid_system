<?php

namespace Database\Factories;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    protected $model = PurchaseRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 5000);
        $tax = round($subtotal * 0.1, 2);

        return [
            'reference_number' => fake()->unique()->bothify('PR-####-??'),
            'requester_id' => null,
            'department' => fake()->optional()->word(),
            'request_date' => fake()->date(),
            'needed_by' => fake()->optional()->date(),
            'status' => PurchaseRequestStatus::Draft,
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => $tax,
            'total_amount' => $subtotal + $tax,
            'currency_code' => 'IQD',
            'notes' => fake()->optional()->paragraph(),
            'attachments' => null,
            'converted_rfq_id' => null,
            'converted_at' => null,
        ];
    }
}
