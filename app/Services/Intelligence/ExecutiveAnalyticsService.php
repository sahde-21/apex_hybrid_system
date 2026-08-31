<?php

namespace App\Services\Intelligence;

use App\Models\User;
use App\Services\Analytics\TrendAnalysisService;
use App\Services\Bi\BiAnalyticsService;
use App\Services\Bi\BiChartService;
use App\Services\Bi\BiKpiService;
use App\Services\Bi\BiReportService;
use App\Services\Forecasting\ForecastingService;
use App\Services\Scoring\BusinessHealthScoreService;
use App\Support\Analytics\AnalyticsFilter;
use App\Support\Analytics\ScopesAnalytics;
use Illuminate\Support\Facades\Cache;

class ExecutiveAnalyticsService
{
    use ScopesAnalytics;

    public function __construct(
        protected BiAnalyticsService $bi,
        protected BiKpiService $kpis,
        protected BiChartService $charts,
        protected BusinessHealthScoreService $health,
        protected TrendAnalysisService $trends,
        protected ForecastingService $forecasting,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(User $user, AnalyticsFilter $filter): array
    {
        $this->requirePermission($user, config('intelligence.permissions.executive'));

        return Cache::remember($filter->cacheKey($user, 'executive'), config('intelligence.cache_ttl'), function () use ($user, $filter) {
            $bi = $this->bi->dashboard($user, $filter->bi);
            $health = $this->health->score($user, $filter);
            $revenueSeries = $this->extractSeries($bi['charts']['revenue_trend'] ?? []);
            $trend = $this->trends->analyze($revenueSeries);
            $forecast = $this->forecasting->forecast($revenueSeries, (int) config('intelligence.forecast_horizon_periods', 3));

            return [
                'filter' => $filter->toArray(),
                'kpis' => $bi['kpis'],
                'charts' => $bi['charts'],
                'rankings' => $bi['rankings'],
                'health_score' => $health->toArray(),
                'revenue_trend' => $trend->toArray(),
                'revenue_forecast' => $forecast->toArray(),
                'meta' => $this->metadata(__('scf.intelligence.executive_title')),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function forecasts(User $user, AnalyticsFilter $filter): array
    {
        $this->requirePermission($user, config('intelligence.permissions.forecasts'));

        return Cache::remember($filter->cacheKey($user, 'forecasts'), config('intelligence.cache_ttl'), function () use ($user, $filter) {
            $bi = $this->bi->dashboard($user, $filter->bi);
            $revenueSeries = $this->extractSeries($bi['charts']['revenue_trend'] ?? []);
            $trend = $this->trends->analyze($revenueSeries);
            $forecast = $this->forecasting->forecast($revenueSeries, (int) config('intelligence.forecast_horizon_periods', 3));

            return [
                'filter' => $filter->toArray(),
                'revenue_trend' => $trend->toArray(),
                'revenue_forecast' => $forecast->toArray(),
                'meta' => $this->metadata(__('scf.intelligence.forecasts_title'), __('scf.intelligence.estimated_value')),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $chart
     * @return list<float>
     */
    protected function extractSeries(array $chart): array
    {
        $data = $chart['datasets'][0]['data'] ?? [];

        return array_values(array_map('floatval', $data));
    }
}
