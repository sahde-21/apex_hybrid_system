<?php

namespace App\DTOs\Analytics;

final class HealthScoreDrivers
{
    /** @var array<int, string> */
    public array $positive = [];

    /** @var array<int, string> */
    public array $negative = [];

    /**
     * @return list<string>
     */
    public function topPositive(int $limit = 5): array
    {
        return array_slice($this->positive, 0, $limit);
    }

    /**
     * @return list<string>
     */
    public function topNegative(int $limit = 5): array
    {
        return array_slice($this->negative, 0, $limit);
    }
}
