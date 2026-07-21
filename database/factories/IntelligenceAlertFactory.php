<?php

namespace Database\Factories;

use App\Enums\Analytics\InsightSeverity;
use App\Enums\Analytics\InsightStatus;
use App\Models\IntelligenceAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntelligenceAlert>
 */
class IntelligenceAlertFactory extends Factory
{
    protected $model = IntelligenceAlert::class;

    public function definition(): array
    {
        return [
            'rule_key' => 'test_alert',
            'category' => 'financial',
            'severity' => InsightSeverity::Medium,
            'status' => InsightStatus::Active,
            'title' => fake()->sentence(4),
            'summary' => fake()->sentence(),
            'explanation' => fake()->paragraph(),
            'metrics' => ['value' => fake()->randomFloat(2, 100, 10000)],
            'source_references' => [],
            'detected_at' => now(),
        ];
    }
}
