<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Contact;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('REF-######'),
            'contact_id' => Contact::factory(),
            'subject' => fake()->words(3, true),
            'priority' => 'medium',
            'status' => fake()->randomElement(array_column(TicketStatus::cases(), 'value')),
            'description' => fake()->paragraph(),
        ];
    }
}
