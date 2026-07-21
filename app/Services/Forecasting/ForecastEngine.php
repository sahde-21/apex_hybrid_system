<?php

namespace App\Services\Forecasting;

use App\DTOs\Analytics\ForecastResult;

interface ForecastEngine
{
    /**
     * @param  list<float>  $series
     */
    public function forecast(array $series, int $horizon): ForecastResult;
}
