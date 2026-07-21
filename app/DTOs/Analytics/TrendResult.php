<?php

namespace App\DTOs\Analytics;

use App\Enums\Analytics\TrendDirection;

final readonly class TrendResult
{
    /**
     * @param  list<float>  $series
     */
    public function __construct(
        public TrendDirection $direction,
        public float $absoluteChange,
        public ?float $percentageChange,
        public float $movingAverage,
        public string $confidence,
        public array $series,
        public string $method,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'direction' => $this->direction->value,
            'absolute_change' => round($this->absoluteChange, 4),
            'percentage_change' => $this->percentageChange !== null ? round($this->percentageChange, 4) : null,
            'moving_average' => round($this->movingAverage, 4),
            'confidence' => $this->confidence,
            'series' => $this->series,
            'method' => $this->method,
        ];
    }
}
