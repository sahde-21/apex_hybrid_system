<?php

namespace App\Services\Dashboard;

use App\Enums\LeadStatus;
use App\Enums\TicketStatus;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\StockTransfer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardMetricsService
{
    /**
     * @return array{
     *     products: int,
     *     contacts: int,
     *     sale_orders: int,
     *     invoices: int,
     *     expenses_total: float,
     *     employees: int,
     *     open_tickets: int,
     *     low_stock_products: int,
     *     purchase_orders: int,
     *     payments_total: float,
     *     open_leads: int,
     *     production_orders: int,
     *     stock_transfers: int
     * }
     */
    public function metrics(?User $user = null): array
    {
        $user ??= Auth::user();
        $ttl = (int) config('bi.cache_ttl', 300);
        $cacheKey = config('bi.cache_prefix', 'scf:bi:').'dashboard-metrics:'.($user?->id ?? 'guest');

        return Cache::remember($cacheKey, $ttl, function () use ($user) {
            return [
                'products' => $this->allowed($user, 'products.read') ? Product::query()->count() : 0,
                'contacts' => $this->allowed($user, 'contacts.read') ? Contact::query()->count() : 0,
                'sale_orders' => $this->allowed($user, 'sale-orders.read') ? SaleOrder::query()->count() : 0,
                'invoices' => $this->allowed($user, 'invoices.read') ? Invoice::query()->count() : 0,
                'expenses_total' => $this->allowed($user, 'expenses.read') ? (float) Expense::query()->sum('amount') : 0.0,
                'employees' => $this->allowed($user, 'employees.read') ? Employee::query()->count() : 0,
                'open_tickets' => $this->allowed($user, 'tickets.read')
                    ? Ticket::query()->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress])->count()
                    : 0,
                'low_stock_products' => $this->allowed($user, 'products.read')
                    ? Product::query()->whereColumn('stock_quantity', '<=', 'minimum_stock_level')->count()
                    : 0,
                'purchase_orders' => $this->allowed($user, 'purchase-orders.read') ? PurchaseOrder::query()->count() : 0,
                'payments_total' => $this->allowed($user, 'payments.read') ? (float) Payment::query()->sum('amount') : 0.0,
                'open_leads' => $this->allowed($user, 'leads.read')
                    ? Lead::query()->whereIn('status', [LeadStatus::New, LeadStatus::Contacted, LeadStatus::Qualified])->count()
                    : 0,
                'production_orders' => $this->allowed($user, 'production-orders.read') ? ProductionOrder::query()->count() : 0,
                'stock_transfers' => $this->allowed($user, 'stock-transfers.read') ? StockTransfer::query()->count() : 0,
            ];
        });
    }

    /**
     * @param  array<string, int|float>  $metrics
     * @return array<int, array{label: string, value: float|int, max: float|int}>
     */
    public function snapshotBars(array $metrics): array
    {
        $items = [
            ['key' => 'sale_orders', 'value' => $metrics['sale_orders'] ?? 0],
            ['key' => 'purchase_orders', 'value' => $metrics['purchase_orders'] ?? 0],
            ['key' => 'invoices', 'value' => $metrics['invoices'] ?? 0],
            ['key' => 'open_leads', 'value' => $metrics['open_leads'] ?? 0],
            ['key' => 'open_tickets', 'value' => $metrics['open_tickets'] ?? 0],
            ['key' => 'production_orders', 'value' => $metrics['production_orders'] ?? 0],
        ];

        $max = max(1, ...(array_column($items, 'value') ?: [1]));

        return array_map(fn (array $item) => [
            'label' => __('scf.dashboard_page.'.$item['key']),
            'value' => $item['value'],
            'max' => $max,
        ], $items);
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function recentActivity(int $limit = 8): Collection
    {
        $user = Auth::user();

        if ($user === null || ! $user->can('audit-logs.read')) {
            return collect();
        }

        return AuditLog::query()
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    protected function allowed(?User $user, string $permission): bool
    {
        return $user !== null && $user->can($permission);
    }
}
