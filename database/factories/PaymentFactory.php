<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('PAY-####-??'),
            'contact_id' => null,
            'invoice_id' => null,
            'payment_date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 0, 10000),
            'type' => PaymentType::Incoming,
            'status' => PaymentStatus::Posted,
            'payment_method' => fake()->optional()->word(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
