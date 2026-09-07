<?php

namespace App\Services\Scoring;

use App\DTOs\Analytics\HealthScoreDrivers;
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
            $drivers = new HealthScoreDrivers;

            $categories['financial'] = $this->normalizeFinancial($kpi, $drivers);
            $categories['sales'] = $this->normalizeSales($kpi, $drivers);
            $categories['cash_collection'] = $this->normalizeCash($kpi, $drivers);
            $categories['purchasing'] = $this->normalizePurchasing($kpi, $drivers);
            $categories['inventory'] = $this->normalizeInventory($kpi, $drivers);
            $categories['customers'] = $this->normalizeCustomers($kpi, $drivers);
            $categories['suppliers'] = $this->normalizeSuppliers($kpi, $drivers);
            $categories['operations'] = $this->normalizeOperations($kpi, $drivers);
            $categories['system'] = 75.0;

            $totalWeight = 0;
            $weighted = 0.0;
            foreach ($weights as $key => $weight) {
                if (! array_key_exists($key, $categories) || $categories[$key] === null) {
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
                    positiveDrivers: array_values($drivers->positive),
                    negativeDrivers: array_values($drivers->negative),
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
                positiveDrivers: $drivers->topPositive(),
                negativeDrivers: $drivers->topNegative(),
                unavailable: $unavailable,
                confidence: $confidence,
                generatedAt: now()->toIso8601String(),
            );
        });
    }

    /**
     * @param  array<string, float|int|null>  $kpi
     */
    protected function normalizeFinancial(array $kpi, HealthScoreDrivers $drivers): ?float
    {
        if (($kpi['revenue'] ?? 0) <= 0 && ($kpi['expenses'] ?? 0) <= 0) {
            return null;
        }
        $margin = ($kpi['revenue'] ?? 0) > 0
            ? (($kpi['gross_profit'] ?? 0) / $kpi['revenue']) * 100
            : 0;
        $score = min(100, max(0, 50 + $margin));
        $margin > 20 ? $drivers->positive[] = __('scf.intelligence.driver_gross_margin') : $drivers->negative[] = __('scf.intelligence.driver_low_margin');

        return $score;
    }

    /**
     * @param  array<string, float|int|null>  $kpi
     */
    protected function normalizeSales(array $kpi, HealthScoreDrivers $drivers): ?float
    {
        $salesMonth = (float) ($kpi['sales_month'] ?? 0);
        if ($salesMonth <= 0) {
            return null;
        }
        $salesWeek = (float) ($kpi['sales_week'] ?? 0);
        $salesMonth > $salesWeek
            ? $drivers->positive[] = __('scf.intelligence.driver_sales_momentum')
            : $drivers->negative[] = __('scf.intelligence.driver_sales_slowdown');

        return min(100, 40 + min(60, $salesMonth / max(1, $salesWeek) * 10));
    }

    /**
     * @param  array<string, float|int|null>  $kpi
     */
    protected function normalizeCash(array $kpi, HealthScoreDrivers $drivers): ?float
    {
        $cash = $kpi['cash_flow'] ?? null;
        if ($cash === null) {
            return null;
        }
        $cash >= 0 ? $drivers->positive[] = __('scf.intelligence.driver_positive_cash') : $drivers->negative[] = __('scf.intelligence.driver_negative_cash');

        return min(100, max(0, 50 + ($cash / max(1, abs($kpi['revenue'] ?? 1)) * 50)));
    }

    /**
     * @param  array<string, float|int|null>  $kpi
     */
    protected function normalizePurchasing(array $kpi, HealthScoreDrivers $drivers): ?float
    {
        $bills = $kpi['outstanding_bills'] ?? 0;
        $bills > 0 ? $drivers->negative[] = __('scf.intelligence.driver_outstanding_bills') : $drivers->positive[] = __('scf.intelligence.driver_bills_current');

        return min(100, max(0, 100 - min(50, $bills / 100)));
    }

    /**
     * @param  array<string, float|int|null>  $kpi
     */
    protected function normalizeInventory(array $kpi, HealthScoreDrivers $drivers): ?float
    {
        $low = (int) ($kpi['low_stock'] ?? 0);
        $low > 0 ? $drivers->negative[] = __('scf.intelligence.driver_low_stock', ['count' => $low]) : $drivers->positive[] = __('scf.intelligence.driver_stock_ok');

        return min(100, max(0, 100 - ($low * 5)));
    }

    /**
     * @param  array<string, float|int|null>  $kpi
     */
    protected function normalizeCustomers(array $kpi, HealthScoreDrivers $drivers): ?float
    {
        $outstanding = $kpi['outstanding_invoices'] ?? 0;
        ($kpi['customers'] ?? 0) > 0 ? $drivers->positive[] = __('scf.intelligence.driver_customer_base') : null;
        $outstanding > 0 ? $drivers->negative[] = __('scf.intelligence.driver_outstanding_receivables') : $drivers->positive[] = __('scf.intelligence.driver_receivables_ok');

        return min(100, max(0, 70 - min(40, $outstanding / 100)));
    }

    /**
     * @param  array<string, float|int|null>  $kpi
     */
    protected function normalizeSuppliers(array $kpi, HealthScoreDrivers $drivers): ?float
    {
        return 70.0;
    }

    /**
     * @param  array<string, float|int|null>  $kpi
     */
    protected function normalizeOperations(array $kpi, HealthScoreDrivers $drivers): ?float
    {
        $tickets = (int) ($kpi['open_tickets'] ?? 0);
        $tickets > 5 ? $drivers->negative[] = __('scf.intelligence.driver_open_tickets', ['count' => $tickets]) : $drivers->positive[] = __('scf.intelligence.driver_tickets_ok');

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
