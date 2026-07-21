<?php

namespace Database\Factories;

use App\Enums\Analytics\InsightSeverity;
use App\Enums\Analytics\InsightStatus;
use App\Models\IntelligenceRecommendation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntelligenceRecommendation>
 */
class IntelligenceRecommendationFactory extends Factory
{
    protected $model = IntelligenceRecommendation::class;

    public function definition(): array
    {
        return [
            'rule_key' => 'test_recommendation',
            'category' => 'sales',
            'severity' => InsightSeverity::Low,
            'status' => InsightStatus::Active,
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'reason' => fake()->sentence(),
            'suggested_action' => fake()->sentence(),
            'metrics' => ['count' => fake()->numberBetween(1, 20)],
            'source_references' => [],
            'generated_at' => now(),
        ];
    }
}
