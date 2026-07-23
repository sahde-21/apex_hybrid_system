<?php

namespace App\Services\Intelligence;

use App\Models\User;
use App\Services\Alerts\SmartAlertService;
use App\Services\Bi\BiKpiService;
use App\Services\Forecasting\ForecastingService;
use App\Services\Recommendations\RecommendationEngine;
use App\Services\Scoring\BusinessHealthScoreService;
use App\Support\Analytics\AnalyticsFilter;
use App\Support\Analytics\ScopesAnalytics;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SmartAssistantService
{
    use ScopesAnalytics;

    public function __construct(
        protected BiKpiService $kpis,
        protected DomainAnalyticsService $domains,
        protected BusinessHealthScoreService $health,
        protected ForecastingService $forecasting,
        protected SmartAlertService $alerts,
        protected RecommendationEngine $recommendations,
        protected InsightExplanationService $explanations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function ask(User $user, string $question, AnalyticsFilter $filter): array
    {
        abort_unless($user->can(config('intelligence.permissions.assistant')), 403);
        abort_unless(config('intelligence.assistant_enabled', true), 403);

        $normalized = Str::lower(trim(Str::limit($question, 500, '')));
        $intent = $this->resolveIntent($normalized);

        if ($intent === null) {
            return [
                'supported' => false,
                'message' => __('scf.intelligence.assistant_unsupported'),
                'suggestions' => $this->suggestions(),
            ];
        }

        return [
            'supported' => true,
            'intent' => $intent,
            'response' => $this->handleIntent($user, $intent, $filter),
            'disclaimer' => __('scf.intelligence.assistant_disclaimer'),
        ];
    }

    /**
     * @return list<string>
     */
    public function suggestions(): array
    {
        return [
            __('scf.intelligence.assistant_q_sales_month'),
            __('scf.intelligence.assistant_q_overdue_invoices'),
            __('scf.intelligence.assistant_q_low_stock'),
            __('scf.intelligence.assistant_q_health_score'),
            __('scf.intelligence.assistant_q_alerts'),
        ];
    }

    protected function resolveIntent(string $question): ?string
    {
        $intents = [
            'sales_month' => ['sales this month', 'month revenue', 'مبيعات الشهر', 'فرۆشی ئەم مانگە'],
            'overdue_invoices' => ['overdue invoice', 'outstanding receivable', 'فواتير متأخرة', 'پسوولەی دواکەوتوو'],
            'low_stock' => ['low stock', 'مخزون منخفض', 'کۆگای کەم'],
            'health_score' => ['health score', 'business health', 'صحة الأعمال', 'تەندروستی بازرگانی'],
            'alerts' => ['urgent alert', 'show alerts', 'تنبيهات', 'ئاگاداری'],
            'forecast' => ['sales forecast', 'توقع المبيعات', 'پێشبینی فرۆشتن'],
            'top_products' => ['top product', 'best selling', 'أفضل منتج', 'باشترین بەرهەم'],
        ];

        foreach ($intents as $intent => $phrases) {
            foreach ($phrases as $phrase) {
                if (str_contains($question, Str::lower($phrase))) {
                    return $intent;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function handleIntent(User $user, string $intent, AnalyticsFilter $filter): array
    {
        return match ($intent) {
            'sales_month' => $this->card(
                __('scf.intelligence.assistant_sales_month'),
                (string) ($this->kpis->kpis($user, $filter->bi)['sales_month'] ?? 0),
                'invoices.index',
            ),
            'overdue_invoices' => $this->card(
                __('scf.intelligence.assistant_overdue_invoices'),
                (string) ($this->kpis->kpis($user, $filter->bi)['outstanding_invoices'] ?? 0),
                'invoices.index',
            ),
            'low_stock' => $this->card(
                __('scf.intelligence.assistant_low_stock'),
                (string) ($this->domains->forDomain($user, 'inventory', $filter)['kpis']['low_stock_count'] ?? 0),
                'products.index',
            ),
            'health_score' => [
                'title' => __('scf.intelligence.assistant_health_score'),
                'value' => $this->health->score($user, $filter)->toArray(),
                'link' => route('intelligence.executive'),
            ],
            'alerts' => [
                'title' => __('scf.intelligence.assistant_alerts'),
                'items' => $this->alerts->activeForUser($user)->take(5)->map(fn ($a) => [
                    'title' => $a->title,
                    'severity' => $a->severity->value,
                ])->values()->all(),
                'link' => route('intelligence.alerts'),
            ],
            'forecast' => [
                'title' => __('scf.intelligence.assistant_forecast'),
                'value' => app(ExecutiveAnalyticsService::class)->forecasts($user, $filter)['revenue_forecast'] ?? [],
                'link' => route('intelligence.forecasts'),
                'is_estimate' => true,
            ],
            'top_products' => [
                'title' => __('scf.intelligence.assistant_top_products'),
                'chart' => $this->domains->forDomain($user, 'sales', $filter)['charts']['top_products'] ?? [],
                'link' => route('intelligence.sales'),
            ],
            default => ['message' => __('scf.intelligence.assistant_unsupported')],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function card(string $title, string $value, string $route): array
    {
        return [
            'title' => $title,
            'value' => $value,
            'link' => Route::has($route) ? route($route) : null,
        ];
    }
}
