<?php

namespace App\Services\Scoring;

use App\DTOs\Analytics\HealthScoreResult;
use App\Enums\Analytics\HealthScoreLabel;
use App\Models\User;
use App\Services\Bi\BiKpiService;
use App\Support\Analytics\AnalyticsFilter;
use App\Support\Analytics\ScopesAnalytics;
use Illuminate\Support\Facades\Cache;

class BusinessHealthScoreService
{
    use ScopesAnalytics;

    public function __construct(
        protected BiKpiService $kpis,
    ) {}

    public function score(User $user, AnalyticsFilter $filter): HealthScoreResult
    {
        $this->requirePermission($user, config('intelligence.permissions.view'));

        return Cache::remember($filter->cacheKey($user, 'health_score'), config('intelligence.cache_ttl'), function () use ($user, $filter) {
            $kpi = $this->kpis->kpis($user, $filter->bi);
            $weights = config('intelligence.health_score_weights', []);
            $categories = [];
            $unavailable = [];
            $positive = [];
            $negative = [];

            $categories['financial'] = $this->normalizeFinancial($kpi, $positive, $negative);
            $categories['sales'] = $this->normalizeSales($kpi, $positive, $negative);
            $categories['cash_collection'] = $this->normalizeCash($kpi, $positive, $negative);
            $categories['purchasing'] = $this->normalizePurchasing($kpi, $positive, $negative);
            $categories['inventory'] = $this->normalizeInventory($kpi, $positive, $negative);
            $categories['customers'] = $this->normalizeCustomers($kpi, $positive, $negative);
            $categories['suppliers'] = $this->normalizeSuppliers($kpi, $positive, $negative);
            $categories['operations'] = $this->normalizeOperations($kpi, $positive, $negative);
            $categories['system'] = 75.0;

            $totalWeight = 0;
            $weighted = 0.0;
            foreach ($weights as $key => $weight) {
                if (! isset($categories[$key]) || $categories[$key] === null) {
                    $unavailable[] = $key;

                    continue;
                }
                $totalWeight += $weight;
                $weighted += $categories[$key] * $weight;
            }

            if ($totalWeight === 0) {
                return new HealthScoreResult(
                    score: null,
                    label: HealthScoreLabel::InsufficientData,
                    categories: $categories,
                    positiveDrivers: $positive,
                    negativeDrivers: $negative,
                    unavailable: $unavailable,
                    confidence: 'low',
                    generatedAt: now()->toIso8601String(),
                );
            }

            $score = $weighted / $totalWeight;
            $label = $this->labelForScore($score);
            $confidence = count($unavailable) > 3 ? 'low' : (count($unavailable) > 0 ? 'medium' : 'high');

            return new HealthScoreResult(
                score: $score,
                label: $label,
                categories: $categories,
                positiveDrivers: array_slice($positive, 0, 5),
                negativeDrivers: array_slice($negative, 0, 5),
                unavailable: $unavailable,
                confidence: $confidence,
                generatedAt: now()->toIso8601String(),
            );
        });
    }

    /**
     * @param  array<string, float|int>  $kpi
     */
    protected function normalizeFinancial(array $kpi, array &$positive, array &$negative): ?float
    {
        if (($kpi['revenue'] ?? 0) <= 0 && ($kpi['expenses'] ?? 0) <= 0) {
            return null;
        }
        $margin = ($kpi['revenue'] ?? 0) > 0
            ? (($kpi['gross_profit'] ?? 0) / $kpi['revenue']) * 100
            : 0;
        $score = min(100, max(0, 50 + $margin));
        $margin > 20 ? $positive[] = __('scf.intelligence.driver_gross_margin') : $negative[] = __('scf.intelligence.driver_low_margin');

        return $score;
    }

    /**
     * @param  array<string, float|int>  $kpi
     */
    protected function normalizeSales(array $kpi, array &$positive, array &$negative): ?float
    {
        if (($kpi['sales_month'] ?? 0) <= 0) {
            return null;
        }
        ($kpi['sales_month'] ?? 0) > ($kpi['sales_week'] ?? 0)
            ? $positive[] = __('scf.intelligence.driver_sales_momentum')
            : $negative[] = __('scf.intelligence.driver_sales_slowdown');

        return min(100, 40 + min(60, ($kpi['sales_month'] ?? 0) / max(1, ($kpi['sales_week'] ?? 1)) * 10));
    }

    /**
     * @param  array<string, float|int>  $kpi
     */
    protected function normalizeCash(array $kpi, array &$positive, array &$negative): ?float
    {
        $cash = $kpi['cash_flow'] ?? null;
        if ($cash === null) {
            return null;
        }
        $cash >= 0 ? $positive[] = __('scf.intelligence.driver_positive_cash') : $negative[] = __('scf.intelligence.driver_negative_cash');

        return min(100, max(0, 50 + ($cash / max(1, abs($kpi['revenue'] ?? 1)) * 50)));
    }

    /**
     * @param  array<string, float|int>  $kpi
     */
    protected function normalizePurchasing(array $kpi, array &$positive, array &$negative): ?float
    {
        $bills = $kpi['outstanding_bills'] ?? 0;
        $bills > 0 ? $negative[] = __('scf.intelligence.driver_outstanding_bills') : $positive[] = __('scf.intelligence.driver_bills_current');

        return min(100, max(0, 100 - min(50, $bills / 100)));
    }

    /**
     * @param  array<string, float|int>  $kpi
     */
    protected function normalizeInventory(array $kpi, array &$positive, array &$negative): ?float
    {
        $low = (int) ($kpi['low_stock'] ?? 0);
        $low > 0 ? $negative[] = __('scf.intelligence.driver_low_stock', ['count' => $low]) : $positive[] = __('scf.intelligence.driver_stock_ok');

        return min(100, max(0, 100 - ($low * 5)));
    }

    /**
     * @param  array<string, float|int>  $kpi
     */
    protected function normalizeCustomers(array $kpi, array &$positive, array &$negative): ?float
    {
        $outstanding = $kpi['outstanding_invoices'] ?? 0;
        ($kpi['customers'] ?? 0) > 0 ? $positive[] = __('scf.intelligence.driver_customer_base') : null;
        $outstanding > 0 ? $negative[] = __('scf.intelligence.driver_outstanding_receivables') : $positive[] = __('scf.intelligence.driver_receivables_ok');

        return min(100, max(0, 70 - min(40, $outstanding / 100)));
    }

    /**
     * @param  array<string, float|int>  $kpi
     */
    protected function normalizeSuppliers(array $kpi, array &$positive, array &$negative): ?float
    {
        return 70.0;
    }

    /**
     * @param  array<string, float|int>  $kpi
     */
    protected function normalizeOperations(array $kpi, array &$positive, array &$negative): ?float
    {
        $tickets = (int) ($kpi['open_tickets'] ?? 0);
        $tickets > 5 ? $negative[] = __('scf.intelligence.driver_open_tickets', ['count' => $tickets]) : $positive[] = __('scf.intelligence.driver_tickets_ok');

        return min(100, max(0, 100 - ($tickets * 3)));
    }

    protected function labelForScore(float $score): HealthScoreLabel
    {
        return match (true) {
            $score >= 85 => HealthScoreLabel::Excellent,
            $score >= 70 => HealthScoreLabel::Healthy,
            $score >= 55 => HealthScoreLabel::Stable,
            $score >= 40 => HealthScoreLabel::NeedsAttention,
            default => HealthScoreLabel::HighRisk,
        };
    }
}
