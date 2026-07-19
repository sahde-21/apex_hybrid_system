<?php

namespace Database\Factories;

use App\Enums\CrmInteractionType;
use App\Models\Contact;
use App\Models\CrmInteraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmInteraction>
 */
class CrmInteractionFactory extends Factory
{
    protected $model = CrmInteraction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'interaction_type' => fake()->randomElement(CrmInteractionType::cases()),
            'subject' => fake()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'interaction_date' => fake()->dateTime(),
            'follow_up_date' => fake()->optional()->date(),
        ];
    }
}