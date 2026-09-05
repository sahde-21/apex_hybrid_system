<?php

namespace App\Services\Analytics;

use App\DTOs\Analytics\TrendResult;
use App\Enums\Analytics\TrendDirection;

class TrendAnalysisService
{
    /**
     * @param  list<float|int>  $series
     */
    public function analyze(array $series, string $method = 'period_over_period'): TrendResult
    {
        $values = array_map('floatval', $series);
        $minPoints = (int) config('intelligence.min_historical_points', 3);

        if (count($values) < $minPoints) {
            return new TrendResult(
                direction: TrendDirection::InsufficientData,
                absoluteChange: 0.0,
                percentageChange: null,
                movingAverage: count($values) ? array_sum($values) / count($values) : 0.0,
                confidence: 'low',
                series: $values,
                method: $method,
            );
        }

        $first = $values[0];
        $last = end($values);
        $absolute = $last - $first;
        $percentage = abs($first) > 0.00001 ? ($absolute / abs($first)) * 100 : null;
        $movingAverage = $this->simpleMovingAverage($values);
        $sensitivity = (float) config('intelligence.trend_sensitivity', 0.05);

        $direction = $this->classifyDirection($percentage, $sensitivity);
        $confidence = count($values) >= 6 ? 'medium' : 'low';

        return new TrendResult(
            direction: $direction,
            absoluteChange: $absolute,
            percentageChange: $percentage,
            movingAverage: $movingAverage,
            confidence: $confidence,
            series: $values,
            method: $method,
        );
    }

    /**
     * @param  list<float|int>  $series
     */
    public function simpleMovingAverage(array $series, ?int $window = null): float
    {
        $window = $window ?? (int) config('intelligence.moving_average_window', 3);
        $values = array_map('floatval', $series);
        $slice = array_slice($values, -$window);

        return count($slice) ? array_sum($slice) / count($slice) : 0.0;
    }

    protected function classifyDirection(?float $percentageChange, float $sensitivity): TrendDirection
    {
        if ($percentageChange === null) {
            return TrendDirection::Stable;
        }

        $pct = $percentageChange / 100;
        $strong = $sensitivity * 2;

        return match (true) {
            $pct >= $strong => TrendDirection::StrongIncrease,
            $pct >= $sensitivity => TrendDirection::ModerateIncrease,
            $pct <= -$strong => TrendDirection::StrongDecrease,
            $pct <= -$sensitivity => TrendDirection::ModerateDecrease,
            default => TrendDirection::Stable,
        };
    }
}
