<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => fake()->unique()->bothify('??-####'),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'budget' => fake()->randomFloat(2, 0, 10000),
            'status' => fake()->randomElement(array_column(CampaignStatus::cases(), 'value')),
            'description' => fake()->paragraph(),
        ];
    }
}
