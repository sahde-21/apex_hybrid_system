<?php

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\PortalCustomer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<PortalCustomer>
 */
class PortalCustomerFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory()->customer()->state([
                'email' => fake()->unique()->safeEmail(),
                'type' => ContactType::Customer,
            ]),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'locale' => 'en',
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withTwoFactor(): static
    {
        return $this->state(fn () => [
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt('TESTSECRET234567'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1', 'recovery-code-2'])),
        ]);
    }

    public function forContact(Contact $contact): static
    {
        return $this->state(fn () => [
            'contact_id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email ?? fake()->unique()->safeEmail(),
            'phone' => $contact->phone,
        ]);
    }
}
