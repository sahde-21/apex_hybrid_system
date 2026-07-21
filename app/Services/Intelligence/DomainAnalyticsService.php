<?php

namespace App\Services\Intelligence;

use App\Enums\BillStatus;
use App\Enums\ContactType;
use App\Enums\InvoiceStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SaleOrderStatus;
use App\Models\Bill;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Accounting\FinancialStatementService;
use App\Services\Bi\BiChartService;
use App\Services\Bi\BiKpiService;
use App\Services\Bi\BiReportService;
use App\Support\Analytics\AnalyticsFilter;
use App\Support\Analytics\ScopesAnalytics;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DomainAnalyticsService
{
    use ScopesAnalytics;

    public function __construct(
        protected BiKpiService $kpis,
        protected BiChartService $charts,
        protected BiReportService $reports,
        protected FinancialStatementService $statements,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forDomain(User $user, string $domain, AnalyticsFilter $filter): array
    {
        $permission = config("intelligence.permissions.{$domain}", config('intelligence.permissions.view'));
        $this->requirePermission($user, $permission);

        return Cache::remember($filter->cacheKey($user, "domain:{$domain}"), config('intelligence.cache_ttl'), function () use ($user, $domain, $filter) {
            return match ($domain) {
                'financial' => $this->financial($user, $filter),
                'sales' => $this->sales($user, $filter),
                'purchasing' => $this->purchasing($user, $filter),
                'inventory' => $this->inventory($user, $filter),
                'customers' => $this->customers($user, $filter),
                'suppliers' => $this->suppliers($user, $filter),
                'operations' => $this->operations($user, $filter),
                default => ['error' => 'unknown_domain'],
            };
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function financial(User $user, AnalyticsFilter $filter): array
    {
        $kpis = $this->kpis->kpis($user, $filter->bi);
        $report = $user->can('financial-reports.read')
            ? $this->reports->report($user, 'financial', $filter->bi)
            : ['title' => '', 'headers' => [], 'rows' => []];

        return [
            'kpis' => array_intersect_key($kpis, array_flip(['revenue', 'gross_profit', 'net_profit', 'expenses', 'cash_flow', 'outstanding_invoices', 'outstanding_bills'])),
            'charts' => [
                'revenue_trend' => $this->charts->build($user, $filter->bi, 'revenue_trend'),
                'expenses_trend' => $this->charts->build($user, $filter->bi, 'expenses_trend'),
                'cash_flow' => $this->charts->build($user, $filter->bi, 'cash_flow'),
            ],
            'report' => $report,
            'meta' => $this->metadata(__('scf.intelligence.financial_title'), $user->can('ledgers.read') ? null : __('scf.intelligence.gl_limited_warning')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function sales(User $user, AnalyticsFilter $filter): array
    {
        $from = $filter->from()->toDateString();
        $to = $filter->to()->toDateString();

        $invoiceCount = $user->can('invoices.read')
            ? Invoice::query()->whereBetween('invoice_date', [$from, $to])->count() : 0;
        $orderCount = $user->can('sale-orders.read')
            ? SaleOrder::query()->whereBetween('order_date', [$from, $to])->count() : 0;
        $cancelled = $user->can('sale-orders.read')
            ? SaleOrder::query()->whereBetween('order_date', [$from, $to])->where('status', SaleOrderStatus::Cancelled)->count() : 0;

        return [
            'kpis' => [
                'invoice_count' => $invoiceCount,
                'order_count' => $orderCount,
                'cancelled_rate' => $orderCount > 0 ? round(($cancelled / $orderCount) * 100, 2) : 0,
                'revenue' => $this->kpis->kpis($user, $filter->bi)['revenue'] ?? 0,
            ],
            'charts' => [
                'revenue_trend' => $this->charts->build($user, $filter->bi, 'revenue_trend'),
                'top_products' => $this->charts->build($user, $filter->bi, 'top_products'),
                'top_customers' => $this->charts->build($user, $filter->bi, 'top_customers'),
            ],
            'meta' => $this->metadata(__('scf.intelligence.sales_title')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function purchasing(User $user, AnalyticsFilter $filter): array
    {
        $from = $filter->from()->toDateString();
        $to = $filter->to()->toDateString();
        $poCount = $user->can('purchase-orders.read')
            ? PurchaseOrder::query()->whereBetween('order_date', [$from, $to])->count() : 0;
        $spend = $user->can('purchase-orders.read')
            ? (float) PurchaseOrder::query()->whereBetween('order_date', [$from, $to])->whereNot('status', PurchaseOrderStatus::Cancelled)->sum('total_amount') : 0;

        return [
            'kpis' => [
                'purchase_orders' => $poCount,
                'purchase_spend' => round($spend, 2),
                'outstanding_bills' => $this->kpis->kpis($user, $filter->bi)['outstanding_bills'] ?? 0,
            ],
            'charts' => [],
            'report' => $user->can('purchase-orders.read') ? $this->reports->report($user, 'purchase', $filter->bi) : null,
            'meta' => $this->metadata(__('scf.intelligence.purchasing_title')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function inventory(User $user, AnalyticsFilter $filter): array
    {
        $kpis = $this->kpis->kpis($user, $filter->bi);
        $lowStock = $user->can('products.read')
            ? Product::query()->whereColumn('stock_quantity', '<=', 'minimum_stock_level')->limit(20)->get(['id', 'name', 'sku', 'stock_quantity', 'minimum_stock_level'])
            : collect();

        return [
            'kpis' => [
                'inventory_value' => $kpis['inventory_value'] ?? 0,
                'low_stock_count' => $kpis['low_stock'] ?? 0,
            ],
            'low_stock_products' => $lowStock->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => $p->stock_quantity,
                'minimum' => $p->minimum_stock_level,
            ])->all(),
            'charts' => [
                'inventory_value' => $this->charts->build($user, $filter->bi, 'inventory_value'),
            ],
            'meta' => $this->metadata(__('scf.intelligence.inventory_title')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function customers(User $user, AnalyticsFilter $filter): array
    {
        abort_unless($user->can('contacts.read'), 403);

        $segments = $this->rfmSegments($filter);

        return [
            'kpis' => [
                'total_customers' => Contact::query()->whereIn('type', [ContactType::Customer->value, ContactType::Both->value])->count(),
                'active_customers' => $segments['active'],
            ],
            'rfm_segments' => $segments['segments'],
            'charts' => [
                'top_customers' => $this->charts->build($user, $filter->bi, 'top_customers'),
            ],
            'meta' => $this->metadata(__('scf.intelligence.customers_title')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function suppliers(User $user, AnalyticsFilter $filter): array
    {
        abort_unless($user->can('contacts.read'), 403);

        $spendBySupplier = Bill::query()
            ->select('contact_id', DB::raw('SUM(total_amount) as spend'))
            ->whereBetween('bill_date', [$filter->from()->toDateString(), $filter->to()->toDateString()])
            ->whereIn('status', [BillStatus::Received, BillStatus::Paid, BillStatus::Overdue])
            ->groupBy('contact_id')
            ->orderByDesc('spend')
            ->limit(10)
            ->get();

        return [
            'kpis' => [
                'supplier_count' => Contact::query()->whereIn('type', [ContactType::Supplier->value, ContactType::Both->value])->count(),
                'outstanding_bills' => $this->kpis->kpis($user, $filter->bi)['outstanding_bills'] ?? 0,
            ],
            'top_suppliers' => $spendBySupplier,
            'meta' => $this->metadata(__('scf.intelligence.suppliers_title')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function operations(User $user, AnalyticsFilter $filter): array
    {
        $kpis = $this->kpis->kpis($user, $filter->bi);

        return [
            'kpis' => [
                'open_tickets' => $kpis['open_tickets'] ?? 0,
                'open_leads' => $kpis['open_leads'] ?? 0,
                'production_orders' => $kpis['production_orders'] ?? 0,
            ],
            'queue_summary' => app(\App\Services\Deployment\QueueStatusService::class)->status(),
            'meta' => $this->metadata(__('scf.intelligence.operations_title')),
        ];
    }

    /**
     * @return array{active: int, segments: array<string, int>}
     */
    protected function rfmSegments(AnalyticsFilter $filter): array
    {
        $from = $filter->from();
        $customers = Contact::query()->whereIn('type', [ContactType::Customer->value, ContactType::Both->value])->pluck('id');
        $segments = [
            'champions' => 0,
            'loyal' => 0,
            'at_risk' => 0,
            'dormant' => 0,
        ];
        $active = 0;

        foreach ($customers as $customerId) {
            $invoices = Invoice::query()
                ->where('contact_id', $customerId)
                ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Paid, InvoiceStatus::Overdue])
                ->where('invoice_date', '>=', $from->toDateString())
                ->get(['total_amount', 'invoice_date']);

            if ($invoices->isEmpty()) {
                $segments['dormant']++;

                continue;
            }

            $active++;
            $monetary = $invoices->sum('total_amount');
            $frequency = $invoices->count();
            $recencyDays = now()->diffInDays($invoices->max('invoice_date'));

            if ($monetary > 1000 && $frequency >= 3 && $recencyDays <= 30) {
                $segments['champions']++;
            } elseif ($frequency >= 2 && $recencyDays <= 60) {
                $segments['loyal']++;
            } elseif ($recencyDays > 90) {
                $segments['at_risk']++;
            } else {
                $segments['loyal']++;
            }
        }

        return ['active' => $active, 'segments' => $segments];
    }
}
