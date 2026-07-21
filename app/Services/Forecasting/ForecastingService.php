<?php

namespace App\Services\Forecasting;

use App\DTOs\Analytics\ForecastResult;
use App\Services\Analytics\TrendAnalysisService;

class ForecastingService implements ForecastEngine
{
    public function __construct(
        protected TrendAnalysisService $trends,
    ) {}

    /**
     * @param  list<float>  $series
     */
    public function forecast(array $series, int $horizon): ForecastResult
    {
        if (! config('intelligence.forecasting_enabled', true)) {
            return $this->insufficient('disabled');
        }

        $values = array_values(array_map('floatval', $series));
        $min = (int) config('intelligence.min_historical_points', 3);

        if (count($values) < $min) {
            return $this->insufficient('insufficient_history');
        }

        $horizon = max(1, min($horizon, (int) config('intelligence.forecast_horizon_periods', 3)));
        $method = count($values) >= 4 ? 'linear_regression' : 'moving_average';

        $forecast = $method === 'linear_regression'
            ? $this->linearRegressionForecast($values, $horizon)
            : $this->movingAverageForecast($values, $horizon);

        $confidence = count($values) >= 6 ? 'medium' : 'low';

        return new ForecastResult(
            method: $method,
            historical: $values,
            forecast: $forecast,
            confidence: $confidence,
            horizon: $horizon,
            isEstimate: true,
        );
    }

    /**
     * @param  list<float>  $values
     * @return list<float>
     */
    protected function movingAverageForecast(array $values, int $horizon): array
    {
        $window = (int) config('intelligence.moving_average_window', 3);
        $base = $this->trends->simpleMovingAverage($values, $window);
        $forecast = [];

        for ($i = 0; $i < $horizon; $i++) {
            $forecast[] = round(max(0, $base), 2);
        }

        return $forecast;
    }

    /**
     * @param  list<float>  $values
     * @return list<float>
     */
    protected function linearRegressionForecast(array $values, int $horizon): array
    {
        $n = count($values);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $values[$i];
            $sumXY += $i * $values[$i];
            $sumX2 += $i * $i;
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        $slope = abs($denominator) < 0.00001 ? 0 : (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        $forecast = [];
        for ($i = $n; $i < $n + $horizon; $i++) {
            $forecast[] = round(max(0, $intercept + ($slope * $i)), 2);
        }

        return $forecast;
    }

    protected function insufficient(string $reason): ForecastResult
    {
        return new ForecastResult(
            method: $reason,
            historical: [],
            forecast: [],
            confidence: 'none',
            horizon: 0,
            isEstimate: true,
        );
    }
}
