<?php

namespace App\DTOs\Analytics;

final readonly class ForecastResult
{
    /**
     * @param  list<float>  $historical
     * @param  list<float>  $forecast
     */
    public function __construct(
        public string $method,
        public array $historical,
        public array $forecast,
        public string $confidence,
        public int $horizon,
        public bool $isEstimate,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'historical' => $this->historical,
            'forecast' => $this->forecast,
            'confidence' => $this->confidence,
            'horizon' => $this->horizon,
            'is_estimate' => $this->isEstimate,
        ];
    }
}
