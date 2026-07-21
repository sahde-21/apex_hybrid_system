<?php

namespace App\Services\Recommendations;

use App\Enums\Analytics\InsightSeverity;
use App\Enums\Analytics\InsightStatus;
use App\Models\IntelligenceRecommendation;
use App\Models\User;
use App\Services\Bi\BiKpiService;
use App\Support\Analytics\AnalyticsFilter;
use App\Support\Analytics\ScopesAnalytics;
use Illuminate\Support\Collection;

class RecommendationEngine
{
    use ScopesAnalytics;

    public function __construct(
        protected BiKpiService $kpis,
    ) {}

    /**
     * @return Collection<int, IntelligenceRecommendation>
     */
    public function activeForUser(User $user, ?string $category = null): Collection
    {
        $this->requirePermission($user, config('intelligence.permissions.recommendations_view'));

        return IntelligenceRecommendation::query()
            ->where('status', InsightStatus::Active)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('priority')
            ->latest('generated_at')
            ->limit(100)
            ->get();
    }

    public function acknowledge(User $user, IntelligenceRecommendation $recommendation): IntelligenceRecommendation
    {
        abort_unless($user->can(config('intelligence.permissions.recommendations_manage')), 403);

        $recommendation->update([
            'status' => InsightStatus::Acknowledged,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);

        return $recommendation->fresh();
    }

    public function dismiss(User $user, IntelligenceRecommendation $recommendation): IntelligenceRecommendation
    {
        abort_unless($user->can(config('intelligence.permissions.recommendations_manage')), 403);

        $recommendation->update([
            'status' => InsightStatus::Dismissed,
            'dismissed_at' => now(),
            'dismissed_by' => $user->id,
        ]);

        return $recommendation->fresh();
    }

    public function refresh(User $user, AnalyticsFilter $filter): int
    {
        $kpi = $this->kpis->kpis($user, $filter->bi);
        $created = 0;

        $rules = [
            [
                'rule_key' => 'follow_up_overdue_invoices',
                'category' => 'financial',
                'severity' => InsightSeverity::High,
                'priority' => 'high',
                'title' => __('scf.intelligence.rec_follow_up_invoices'),
                'description' => __('scf.intelligence.rec_follow_up_invoices_desc'),
                'reason' => __('scf.intelligence.rec_follow_up_invoices_reason'),
                'suggested_action' => __('scf.intelligence.rec_follow_up_invoices_action'),
                'action_route' => 'invoices.index',
                'condition' => ($kpi['outstanding_invoices'] ?? 0) > 0,
            ],
            [
                'rule_key' => 'reorder_low_stock',
                'category' => 'inventory',
                'severity' => InsightSeverity::Medium,
                'priority' => 'medium',
                'title' => __('scf.intelligence.rec_reorder_stock'),
                'description' => __('scf.intelligence.rec_reorder_stock_desc'),
                'reason' => __('scf.intelligence.rec_reorder_stock_reason', ['count' => $kpi['low_stock'] ?? 0]),
                'suggested_action' => __('scf.intelligence.rec_reorder_stock_action'),
                'action_route' => 'products.index',
                'condition' => ($kpi['low_stock'] ?? 0) > 0,
            ],
            [
                'rule_key' => 'review_open_tickets',
                'category' => 'operations',
                'severity' => InsightSeverity::Low,
                'priority' => 'low',
                'title' => __('scf.intelligence.rec_review_tickets'),
                'description' => __('scf.intelligence.rec_review_tickets_desc'),
                'reason' => __('scf.intelligence.rec_review_tickets_reason', ['count' => $kpi['open_tickets'] ?? 0]),
                'suggested_action' => __('scf.intelligence.rec_review_tickets_action'),
                'action_route' => 'tickets.index',
                'condition' => ($kpi['open_tickets'] ?? 0) > 0,
            ],
        ];

        foreach ($rules as $rule) {
            if (! $rule['condition']) {
                continue;
            }

            if (IntelligenceRecommendation::query()->where('rule_key', $rule['rule_key'])->where('status', InsightStatus::Active)->exists()) {
                continue;
            }

            IntelligenceRecommendation::query()->create([
                'rule_key' => $rule['rule_key'],
                'category' => $rule['category'],
                'severity' => $rule['severity'],
                'priority' => $rule['priority'],
                'status' => InsightStatus::Active,
                'title' => $rule['title'],
                'description' => $rule['description'],
                'reason' => $rule['reason'],
                'suggested_action' => $rule['suggested_action'],
                'action_route' => $rule['action_route'],
                'generated_at' => now(),
            ]);
            $created++;
        }

        return $created;
    }
}
