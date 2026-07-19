<?php

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'type' => fake()->randomElement(ContactType::cases())->value,
            'company_name' => fake()->optional()->company(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'tax_number' => fake()->optional()->bothify('TAX-########'),
            'opening_balance' => fake()->randomFloat(2, -5000, 5000),
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContactType::Customer->value,
            'opening_balance' => fake()->randomFloat(2, 0, 5000),
        ]);
    }

    public function supplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContactType::Supplier->value,
            'opening_balance' => fake()->randomFloat(2, -5000, 0),
        ]);
    }
}
