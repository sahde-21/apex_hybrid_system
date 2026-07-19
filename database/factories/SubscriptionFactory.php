<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Contact;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'plan_name' => fake()->word(),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 0, 10000),
            'billing_cycle' => 'monthly',
            'status' => fake()->randomElement(array_column(SubscriptionStatus::cases(), 'value')),
        ];
    }
}
