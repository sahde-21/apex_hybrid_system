<?php

namespace App\Services\Analytics;

use App\Enums\Analytics\InsightSeverity;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Analytics\AnalyticsFilter;
use App\Support\Analytics\ScopesAnalytics;
use Illuminate\Support\Collection;

class AnomalyDetectionService
{
    use ScopesAnalytics;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function detect(User $user, AnalyticsFilter $filter): Collection
    {
        $this->requirePermission($user, config('intelligence.permissions.view'));
        $anomalies = collect();

        if ($user->can('invoices.read')) {
            $avg = (float) Invoice::query()
                ->whereBetween('invoice_date', [$filter->from()->toDateString(), $filter->to()->toDateString()])
                ->avg('total_amount');

            $threshold = $avg * (float) config('intelligence.alert_thresholds.large_invoice_multiplier', 3);

            $large = Invoice::query()
                ->whereBetween('invoice_date', [$filter->from()->toDateString(), $filter->to()->toDateString()])
                ->where('total_amount', '>', max($threshold, 1))
                ->limit(10)
                ->get(['id', 'reference_number', 'total_amount']);

            foreach ($large as $invoice) {
                $anomalies->push([
                    'type' => 'large_invoice',
                    'severity' => InsightSeverity::Medium->value,
                    'title' => __('scf.intelligence.anomaly_large_invoice'),
                    'explanation' => __('scf.intelligence.anomaly_large_invoice_explain', [
                        'amount' => number_format((float) $invoice->total_amount, 2),
                        'average' => number_format($avg, 2),
                    ]),
                    'observed' => (float) $invoice->total_amount,
                    'expected' => $avg,
                    'method' => 'threshold_multiplier',
                    'subject_type' => Invoice::class,
                    'subject_id' => $invoice->id,
                    'detected_at' => now()->toIso8601String(),
                ]);
            }
        }

        return $anomalies;
    }
}
