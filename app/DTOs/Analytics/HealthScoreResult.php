<?php

namespace App\DTOs\Analytics;

use App\Enums\Analytics\HealthScoreLabel;

final readonly class HealthScoreResult
{
    /**
     * @param  array<string, float|null>  $categories
     * @param  list<string>  $positiveDrivers
     * @param  list<string>  $negativeDrivers
     * @param  list<string>  $unavailable
     */
    public function __construct(
        public ?float $score,
        public HealthScoreLabel $label,
        public array $categories,
        public array $positiveDrivers,
        public array $negativeDrivers,
        public array $unavailable,
        public string $confidence,
        public string $generatedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score !== null ? round($this->score, 1) : null,
            'label' => $this->label->value,
            'categories' => $this->categories,
            'positive_drivers' => $this->positiveDrivers,
            'negative_drivers' => $this->negativeDrivers,
            'unavailable' => $this->unavailable,
            'confidence' => $this->confidence,
            'generated_at' => $this->generatedAt,
            'disclaimer' => __('scf.intelligence.health_score_disclaimer'),
        ];
    }
}
