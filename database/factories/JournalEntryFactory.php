<?php

namespace Database\Factories;

use App\Enums\JournalEntryStatus;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 0, 10000);

        return [
            'reference_number' => fake()->unique()->bothify('JE-####-??'),
            'entry_date' => fake()->date(),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement([JournalEntryStatus::Draft, JournalEntryStatus::Posted]),
            'total_debit' => $amount,
            'total_credit' => $amount,
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}