<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\GiftCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftCard>
 */
class GiftCardFactory extends Factory
{
    protected $model = GiftCard::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('??-####'),
            'initial_balance' => fake()->randomFloat(2, 0, 10000),
            'current_balance' => fake()->randomFloat(2, 0, 10000),
            'contact_id' => Contact::factory(),
            'expires_at' => fake()->date(),
            'is_active' => fake()->boolean(),
        ];
    }
}
