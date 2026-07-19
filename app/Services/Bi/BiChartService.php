<?php

namespace App\Services\Bi;

use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentType;
use App\Enums\PosSaleStatus;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\QualityControl;
use App\Models\SaleOrder;
use App\Models\User;
use App\Support\Bi\BiFilter;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BiChartService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function charts(User $user, BiFilter $filter): array
    {
        return Cache::remember($filter->cacheKey('charts'), config('bi.cache_ttl'), function () use ($user, $filter) {
            $pack = config('bi.dashboards.'.$filter->dashboard.'.charts', ['revenue_trend', 'profit_mix']);
            $charts = [];

            foreach ($pack as $key) {
                $charts[$key] = $this->build($user, $filter, $key);
            }

            return $charts;
        });
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>, meta?: array<string, mixed>}
     */
    public function build(User $user, BiFilter $filter, string $key): array
    {
        return match ($key) {
            'revenue_trend' => $this->revenueTrend($user, $filter),
            'expenses_trend' => $this->expensesTrend($user, $filter),
            'cash_flow' => $this->cashFlow($user, $filter),
            'profit_mix' => $this->profitMix($user, $filter),
            'top_products' => $this->topProducts($user, $filter),
            'top_customers' => $this->topContacts($user, $filter, ['customer', 'both'], 'invoices'),
            'branch_compare' => $this->branchCompare($user, $filter),
            'monthly_compare' => $this->monthlyCompare($user, $filter),
            'inventory_value' => $this->inventoryBreakdown($user),
            'low_stock' => $this->lowStockChart($user),
            'leads_funnel' => $this->leadsFunnel($user),
            'production_trend' => $this->productionTrend($user, $filter),
            'quality_mix' => $this->qualityMix($user),
            'payroll_cost' => $this->payrollTrend($user, $filter),
            'attendance_heatmap' => $this->attendanceHeatmap($user, $filter),
            'forecast' => $this->forecast($user, $filter),
            default => ['type' => 'bar', 'labels' => [], 'datasets' => []],
        };
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function revenueTrend(User $user, BiFilter $filter): array
    {
        if (! $user->can('invoices.read')) {
            return ['type' => 'line', 'labels' => [], 'datasets' => []];
        }

        $months = $this->monthLabels($filter);
        $invoice = Invoice::query()
            ->selectRaw($this->periodExpr('invoice_date').' as period, SUM(total_amount) as total')
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Paid, InvoiceStatus::Overdue])
            ->whereBetween('invoice_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->when($filter->customerId, fn ($q) => $q->where('contact_id', $filter->customerId))
            ->groupBy('period')
            ->pluck('total', 'period');

        $pos = PosSale::query()
            ->selectRaw($this->periodExpr('created_at').' as period, SUM(total_amount) as total')
            ->where('status', PosSaleStatus::Completed)
            ->where('is_return', false)
            ->whereBetween('created_at', [$filter->from, $filter->to])
            ->groupBy('period')
            ->pluck('total', 'period');

        $data = [];
        foreach ($months as $month) {
            $data[] = round((float) ($invoice[$month] ?? 0) + (float) ($pos[$month] ?? 0), 2);
        }

        return [
            'type' => 'line',
            'labels' => $months,
            'datasets' => [[
                'label' => __('scf.bi.chart_revenue'),
                'data' => $data,
                'fill' => true,
                'tension' => 0.35,
            ]],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function expensesTrend(User $user, BiFilter $filter): array
    {
        if (! $user->can('expenses.read')) {
            return ['type' => 'area', 'labels' => [], 'datasets' => []];
        }

        $months = $this->monthLabels($filter);
        $rows = Expense::query()
            ->selectRaw($this->periodExpr('expense_date').' as period, SUM(amount) as total')
            ->whereBetween('expense_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->groupBy('period')
            ->pluck('total', 'period');

        return [
            'type' => 'bar',
            'labels' => $months,
            'datasets' => [[
                'label' => __('scf.bi.chart_expenses'),
                'data' => array_map(fn ($m) => round((float) ($rows[$m] ?? 0), 2), $months),
            ]],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function cashFlow(User $user, BiFilter $filter): array
    {
        if (! $user->can('payments.read')) {
            return ['type' => 'bar', 'labels' => [], 'datasets' => []];
        }

        $months = $this->monthLabels($filter);
        $in = Payment::query()
            ->selectRaw($this->periodExpr('payment_date').' as period, SUM(amount) as total')
            ->where('type', PaymentType::Incoming)
            ->whereBetween('payment_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->groupBy('period')
            ->pluck('total', 'period');
        $out = Payment::query()
            ->selectRaw($this->periodExpr('payment_date').' as period, SUM(amount) as total')
            ->where('type', PaymentType::Outgoing)
            ->whereBetween('payment_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->groupBy('period')
            ->pluck('total', 'period');

        return [
            'type' => 'bar',
            'labels' => $months,
            'datasets' => [
                [
                    'label' => __('scf.bi.chart_inflow'),
                    'data' => array_map(fn ($m) => round((float) ($in[$m] ?? 0), 2), $months),
                ],
                [
                    'label' => __('scf.bi.chart_outflow'),
                    'data' => array_map(fn ($m) => round((float) ($out[$m] ?? 0), 2), $months),
                ],
            ],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function profitMix(User $user, BiFilter $filter): array
    {
        $kpis = app(BiKpiService::class)->kpis($user, $filter);

        return [
            'type' => 'doughnut',
            'labels' => [
                __('scf.bi.kpi_revenue'),
                __('scf.bi.kpi_expenses'),
                __('scf.bi.kpi_net_profit'),
            ],
            'datasets' => [[
                'data' => [
                    max(0, (float) $kpis['revenue']),
                    max(0, (float) $kpis['expenses']),
                    max(0, (float) $kpis['net_profit']),
                ],
            ]],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function topProducts(User $user, BiFilter $filter): array
    {
        if (! $user->can('pos.read') && ! $user->can('products.read')) {
            return ['type' => 'bar', 'labels' => [], 'datasets' => []];
        }

        $rows = PosSaleItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(line_total) as revenue'))
            ->whereHas('sale', function ($q) use ($filter) {
                $q->where('status', PosSaleStatus::Completed)
                    ->where('is_return', false)
                    ->whereBetween('created_at', [$filter->from, $filter->to]);
            })
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        if ($rows->isEmpty()) {
            $products = Product::query()->orderByDesc('stock_quantity')->limit(8)->get(['id', 'name', 'stock_quantity']);

            return [
                'type' => 'bar',
                'labels' => $products->pluck('name')->all(),
                'datasets' => [[
                    'label' => __('scf.bi.chart_stock'),
                    'data' => $products->pluck('stock_quantity')->map(fn ($v) => (float) $v)->all(),
                ]],
            ];
        }

        return [
            'type' => 'bar',
            'labels' => $rows->map(fn ($r) => $r->product?->name ?? '#'.$r->product_id)->all(),
            'datasets' => [[
                'label' => __('scf.bi.chart_revenue'),
                'data' => $rows->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->all(),
            ]],
        ];
    }

    /**
     * @param  list<string>  $types
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function topContacts(User $user, BiFilter $filter, array $types, string $source): array
    {
        if (! $user->can('invoices.read')) {
            return ['type' => 'bar', 'labels' => [], 'datasets' => []];
        }

        $rows = Invoice::query()
            ->select('contact_id', DB::raw('SUM(total_amount) as total'))
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Paid, InvoiceStatus::Overdue])
            ->whereBetween('invoice_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->whereHas('contact', fn ($q) => $q->whereIn('type', $types))
            ->with('contact:id,name')
            ->groupBy('contact_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'type' => 'bar',
            'labels' => $rows->map(fn ($r) => $r->contact?->name ?? '#'.$r->contact_id)->all(),
            'datasets' => [[
                'label' => __('scf.bi.chart_revenue'),
                'data' => $rows->pluck('total')->map(fn ($v) => round((float) $v, 2))->all(),
            ]],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function branchCompare(User $user, BiFilter $filter): array
    {
        if (! $user->can('branches.read')) {
            return ['type' => 'bar', 'labels' => [], 'datasets' => []];
        }

        $branches = Branch::query()->orderBy('name')->get(['id', 'name']);
        $labels = [];
        $data = [];

        foreach ($branches as $branch) {
            $labels[] = $branch->name;
            $data[] = round((float) PosSale::query()
                ->where('status', PosSaleStatus::Completed)
                ->where('is_return', false)
                ->whereBetween('created_at', [$filter->from, $filter->to])
                ->whereHas('register', fn ($q) => $q->where('branch_id', $branch->id))
                ->sum('total_amount'), 2);
        }

        return [
            'type' => 'bar',
            'labels' => $labels,
            'datasets' => [[
                'label' => __('scf.bi.chart_branch_sales'),
                'data' => $data,
            ]],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function monthlyCompare(User $user, BiFilter $filter): array
    {
        $thisYear = $this->revenueTrend($user, $filter);
        $prevFilter = BiFilter::fromArray([
            'from' => $filter->from->subYear()->toDateString(),
            'to' => $filter->to->subYear()->toDateString(),
            'dashboard' => $filter->dashboard,
        ]);
        $lastYear = $this->revenueTrend($user, $prevFilter);

        return [
            'type' => 'bar',
            'labels' => $thisYear['labels'],
            'datasets' => [
                [
                    'label' => __('scf.bi.chart_this_year'),
                    'data' => $thisYear['datasets'][0]['data'] ?? [],
                ],
                [
                    'label' => __('scf.bi.chart_last_year'),
                    'data' => $lastYear['datasets'][0]['data'] ?? [],
                ],
            ],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function inventoryBreakdown(User $user): array
    {
        if (! $user->can('products.read')) {
            return ['type' => 'pie', 'labels' => [], 'datasets' => []];
        }

        $rows = Product::query()
            ->select('category_id', DB::raw('SUM(stock_quantity * purchase_price) as value'))
            ->with('category:id,name')
            ->groupBy('category_id')
            ->orderByDesc('value')
            ->limit(8)
            ->get();

        return [
            'type' => 'pie',
            'labels' => $rows->map(fn ($r) => $r->category?->name ?? __('Uncategorized'))->all(),
            'datasets' => [[
                'data' => $rows->pluck('value')->map(fn ($v) => round((float) $v, 2))->all(),
            ]],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function lowStockChart(User $user): array
    {
        if (! $user->can('products.read')) {
            return ['type' => 'bar', 'labels' => [], 'datasets' => []];
        }

        $products = Product::query()
            ->whereColumn('stock_quantity', '<=', 'minimum_stock_level')
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get(['name', 'stock_quantity', 'minimum_stock_level']);

        return [
            'type' => 'bar',
            'labels' => $products->pluck('name')->all(),
            'datasets' => [
                [
                    'label' => __('scf.bi.chart_stock'),
                    'data' => $products->pluck('stock_quantity')->map(fn ($v) => (float) $v)->all(),
                ],
                [
                    'label' => __('scf.bi.chart_min_stock'),
                    'data' => $products->pluck('minimum_stock_level')->map(fn ($v) => (float) $v)->all(),
                ],
            ],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function leadsFunnel(User $user): array
    {
        if (! $user->can('leads.read')) {
            return ['type' => 'doughnut', 'labels' => [], 'datasets' => []];
        }

        $labels = [];
        $data = [];
        foreach (LeadStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[] = Lead::query()->where('status', $status)->count();
        }

        return [
            'type' => 'doughnut',
            'labels' => $labels,
            'datasets' => [['data' => $data]],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function productionTrend(User $user, BiFilter $filter): array
    {
        if (! $user->can('production-orders.read')) {
            return ['type' => 'line', 'labels' => [], 'datasets' => []];
        }

        $dateCol = DB::getSchemaBuilder()->hasColumn('production_orders', 'start_date') ? 'start_date' : 'created_at';
        $months = $this->monthLabels($filter);
        $rows = ProductionOrder::query()
            ->selectRaw($this->periodExpr($dateCol).' as period, COUNT(*) as total')
            ->whereBetween($dateCol, [$filter->from->toDateString(), $filter->to->toDateString()])
            ->groupBy('period')
            ->pluck('total', 'period');

        return [
            'type' => 'line',
            'labels' => $months,
            'datasets' => [[
                'label' => __('scf.bi.chart_production'),
                'data' => array_map(fn ($m) => (float) ($rows[$m] ?? 0), $months),
                'fill' => true,
            ]],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function qualityMix(User $user): array
    {
        if (! $user->can('quality-control.read')) {
            return ['type' => 'pie', 'labels' => [], 'datasets' => []];
        }

        $rows = QualityControl::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'type' => 'pie',
            'labels' => $rows->keys()->map(fn ($k) => (string) $k)->all(),
            'datasets' => [['data' => $rows->values()->map(fn ($v) => (float) $v)->all()]],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function payrollTrend(User $user, BiFilter $filter): array
    {
        if (! $user->can('payrolls.read')) {
            return ['type' => 'bar', 'labels' => [], 'datasets' => []];
        }

        $months = $this->monthLabels($filter);
        $rows = \App\Models\Payroll::query()
            ->selectRaw($this->periodExpr('pay_period_start').' as period, SUM(net_amount) as total')
            ->whereBetween('pay_period_start', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->groupBy('period')
            ->pluck('total', 'period');

        return [
            'type' => 'bar',
            'labels' => $months,
            'datasets' => [[
                'label' => __('scf.bi.kpi_payroll_cost'),
                'data' => array_map(fn ($m) => round((float) ($rows[$m] ?? 0), 2), $months),
            ]],
        ];
    }

    /**
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    protected function attendanceHeatmap(User $user, BiFilter $filter): array
    {
        if (! $user->can('attendance.read')) {
            return ['type' => 'heatmap', 'labels' => [], 'datasets' => [], 'meta' => ['cells' => []]];
        }

        $rows = Attendance::query()
            ->selectRaw($this->dayExpr('attendance_date').' as day, COUNT(*) as total')
            ->whereBetween('attendance_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->when($filter->branchId, fn ($q) => $q->where('branch_id', $filter->branchId))
            ->groupBy('day')
            ->pluck('total', 'day');

        $cells = [];
        foreach (CarbonPeriod::create($filter->from, $filter->to) as $day) {
            $key = $day->format('Y-m-d');
            $cells[] = [
                'date' => $key,
                'value' => (int) ($rows[$key] ?? 0),
            ];
        }

        return [
            'type' => 'heatmap',
            'labels' => [],
            'datasets' => [],
            'meta' => ['cells' => $cells],
        ];
    }

    /**
     * Simple linear forecast from trailing monthly averages.
     *
     * @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function forecast(User $user, BiFilter $filter): array
    {
        $history = $this->revenueTrend($user, $filter);
        $values = $history['datasets'][0]['data'] ?? [];
        $avg = count($values) ? array_sum($values) / count($values) : 0;
        $labels = $history['labels'];
        $forecastLabels = [];
        $forecastData = [];

        for ($i = 1; $i <= 3; $i++) {
            $forecastLabels[] = now()->startOfMonth()->addMonths($i)->format('Y-m');
            $forecastData[] = round($avg * (1 + (0.02 * $i)), 2);
        }

        return [
            'type' => 'line',
            'labels' => array_merge($labels, $forecastLabels),
            'datasets' => [
                [
                    'label' => __('scf.bi.chart_actual'),
                    'data' => array_merge($values, array_fill(0, 3, null)),
                    'spanGaps' => false,
                ],
                [
                    'label' => __('scf.bi.chart_forecast'),
                    'data' => array_merge(array_fill(0, count($values), null), $forecastData),
                    'borderDash' => [6, 4],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    protected function monthLabels(BiFilter $filter): array
    {
        $labels = [];
        $cursor = $filter->from->startOfMonth();
        $end = $filter->to->startOfMonth();

        while ($cursor <= $end) {
            $labels[] = $cursor->format('Y-m');
            $cursor = $cursor->addMonth();
        }

        return $labels ?: [now()->format('Y-m')];
    }

    protected function periodExpr(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    protected function dayExpr(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m-%d')";
    }
}
