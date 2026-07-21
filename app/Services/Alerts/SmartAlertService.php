<?php

namespace App\Services\Alerts;

use App\Enums\Analytics\InsightSeverity;
use App\Enums\Analytics\InsightStatus;
use App\Models\IntelligenceAlert;
use App\Models\User;
use App\Services\Bi\BiKpiService;
use App\Support\Analytics\AnalyticsFilter;
use App\Support\Analytics\ScopesAnalytics;
use Illuminate\Support\Collection;

class SmartAlertService
{
    use ScopesAnalytics;

    public function __construct(
        protected BiKpiService $kpis,
    ) {}

    /**
     * @return Collection<int, IntelligenceAlert>
     */
    public function activeForUser(User $user, ?string $category = null): Collection
    {
        $this->requirePermission($user, config('intelligence.permissions.alerts_view'));

        return IntelligenceAlert::query()
            ->where('status', InsightStatus::Active)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->latest('detected_at')
            ->limit(100)
            ->get();
    }

    public function acknowledge(User $user, IntelligenceAlert $alert): IntelligenceAlert
    {
        abort_unless($user->can(config('intelligence.permissions.alerts_manage')), 403);

        $alert->update([
            'status' => InsightStatus::Acknowledged,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);

        return $alert->fresh();
    }

    public function dismiss(User $user, IntelligenceAlert $alert): IntelligenceAlert
    {
        abort_unless($user->can(config('intelligence.permissions.alerts_manage')), 403);

        $alert->update([
            'status' => InsightStatus::Dismissed,
            'dismissed_at' => now(),
            'dismissed_by' => $user->id,
        ]);

        return $alert->fresh();
    }

    /**
     * Evaluate rules and upsert active alerts (idempotent by rule_key + subject).
     */
    public function evaluate(User $user, AnalyticsFilter $filter): int
    {
        $kpi = $this->kpis->kpis($user, $filter->bi);
        $created = 0;

        $rules = [
            [
                'rule_key' => 'overdue_receivables',
                'category' => 'financial',
                'severity' => InsightSeverity::High,
                'title' => __('scf.intelligence.alert_overdue_receivables'),
                'summary' => __('scf.intelligence.alert_overdue_receivables_summary', ['amount' => number_format($kpi['outstanding_invoices'] ?? 0, 2)]),
                'condition' => ($kpi['outstanding_invoices'] ?? 0) > config('intelligence.alert_thresholds.overdue_invoice_amount', 1000),
                'metrics' => ['outstanding_invoices' => $kpi['outstanding_invoices'] ?? 0],
            ],
            [
                'rule_key' => 'low_stock',
                'category' => 'inventory',
                'severity' => InsightSeverity::Medium,
                'title' => __('scf.intelligence.alert_low_stock'),
                'summary' => __('scf.intelligence.alert_low_stock_summary', ['count' => $kpi['low_stock'] ?? 0]),
                'condition' => ($kpi['low_stock'] ?? 0) >= config('intelligence.alert_thresholds.low_stock_count', 5),
                'metrics' => ['low_stock' => $kpi['low_stock'] ?? 0],
            ],
            [
                'rule_key' => 'negative_cash_flow',
                'category' => 'financial',
                'severity' => InsightSeverity::Medium,
                'title' => __('scf.intelligence.alert_negative_cash'),
                'summary' => __('scf.intelligence.alert_negative_cash_summary'),
                'condition' => ($kpi['cash_flow'] ?? 0) < 0,
                'metrics' => ['cash_flow' => $kpi['cash_flow'] ?? 0],
            ],
        ];

        foreach ($rules as $rule) {
            if (! $rule['condition']) {
                continue;
            }

            $exists = IntelligenceAlert::query()
                ->where('rule_key', $rule['rule_key'])
                ->where('status', InsightStatus::Active)
                ->exists();

            if ($exists) {
                continue;
            }

            IntelligenceAlert::query()->create([
                'rule_key' => $rule['rule_key'],
                'category' => $rule['category'],
                'severity' => $rule['severity'],
                'status' => InsightStatus::Active,
                'title' => $rule['title'],
                'summary' => $rule['summary'],
                'explanation' => __('scf.intelligence.alert_rule_explanation', ['rule' => $rule['rule_key']]),
                'metrics' => $rule['metrics'],
                'detected_at' => now(),
            ]);
            $created++;
        }

        return $created;
    }
}
