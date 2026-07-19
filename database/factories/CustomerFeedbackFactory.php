<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\CustomerFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerFeedback>
 */
class CustomerFeedbackFactory extends Factory
{
    protected $model = CustomerFeedback::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'subject' => fake()->words(3, true),
            'feedback' => fake()->paragraph(),
            'feedback_date' => fake()->date(),
        ];
    }
}
