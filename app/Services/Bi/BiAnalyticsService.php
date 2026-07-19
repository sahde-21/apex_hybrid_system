<?php

namespace App\Services\Bi;

use App\Models\User;
use App\Support\Bi\BiFilter;
use Illuminate\Support\Facades\Cache;

class BiAnalyticsService
{
    public function __construct(
        protected BiKpiService $kpis,
        protected BiChartService $charts,
        protected BiReportService $reports,
    ) {}

    /**
     * @return array{
     *     filter: array<string, mixed>,
     *     dashboard: string,
     *     kpis: array<string, float|int>,
     *     charts: array<string, array<string, mixed>>,
     *     rankings: array<string, mixed>
     * }
     */
    public function dashboard(User $user, BiFilter $filter): array
    {
        abort_unless($user->can('analytics.read'), 403);

        $dashboards = config('bi.dashboards', []);
        if (! isset($dashboards[$filter->dashboard])) {
            $filter = BiFilter::fromArray([...$filter->toArray(), 'dashboard' => 'ceo']);
        }

        return [
            'filter' => $filter->toArray(),
            'dashboard' => $filter->dashboard,
            'kpis' => $this->kpis->kpis($user, $filter),
            'charts' => $this->charts->charts($user, $filter),
            'rankings' => $this->rankings($user, $filter),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rankings(User $user, BiFilter $filter): array
    {
        return Cache::remember($filter->cacheKey('rankings'), config('bi.cache_ttl'), function () use ($user, $filter) {
            return [
                'top_products' => $this->charts->build($user, $filter, 'top_products'),
                'top_customers' => $this->charts->build($user, $filter, 'top_customers'),
                'top_suppliers' => $user->can('purchase-orders.read')
                    ? $this->topSuppliers($filter)
                    : ['type' => 'bar', 'labels' => [], 'datasets' => []],
                'top_employees' => $user->can('employees.read')
                    ? $this->topEmployees()
                    : ['type' => 'bar', 'labels' => [], 'datasets' => []],
                'top_branches' => $this->charts->build($user, $filter, 'branch_compare'),
            ];
        });
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function topSuppliers(BiFilter $filter): array
    {
        $rows = \App\Models\PurchaseOrder::query()
            ->select('contact_id', \Illuminate\Support\Facades\DB::raw('SUM(total_amount) as total'))
            ->whereBetween('order_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->with('contact:id,name')
            ->groupBy('contact_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'type' => 'bar',
            'labels' => $rows->map(fn ($r) => $r->contact?->name ?? '#'.$r->contact_id)->all(),
            'datasets' => [[
                'label' => __('scf.bi.chart_purchases'),
                'data' => $rows->pluck('total')->map(fn ($v) => round((float) $v, 2))->all(),
            ]],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function topEmployees(): array
    {
        $employees = \App\Models\Employee::query()
            ->where('is_active', true)
            ->orderByDesc('salary')
            ->limit(8)
            ->get(['id', 'first_name', 'last_name', 'salary']);

        return [
            'type' => 'bar',
            'labels' => $employees->map(fn ($e) => trim($e->first_name.' '.$e->last_name) ?: '#'.$e->id)->all(),
            'datasets' => [[
                'label' => __('scf.bi.kpi_payroll_cost'),
                'data' => $employees->map(fn ($e) => round((float) $e->salary, 2))->all(),
            ]],
        ];
    }
}
