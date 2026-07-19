<?php

namespace App\Services\Bi;

use App\Enums\BillStatus;
use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentType;
use App\Enums\PosSaleStatus;
use App\Enums\TicketStatus;
use App\Models\Bill;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Bi\BiFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class BiKpiService
{
    /**
     * @return array<string, float|int>
     */
    public function kpis(User $user, BiFilter $filter): array
    {
        return Cache::remember($filter->cacheKey('kpis'), config('bi.cache_ttl'), function () use ($user, $filter) {
            $revenue = $this->allowed($user, 'invoices.read')
                ? $this->invoiceRevenue($filter) + $this->posRevenue($filter)
                : 0.0;

            $expenses = $this->allowed($user, 'expenses.read')
                ? (float) $this->expensesQuery($filter)->sum('amount')
                : 0.0;

            $cogsProxy = $this->allowed($user, 'purchase-orders.read')
                ? (float) $this->purchaseOrdersQuery($filter)->sum('total_amount') * 0.35
                : 0.0;

            $grossProfit = $revenue - $cogsProxy;
            $netProfit = $revenue - $expenses - ($cogsProxy * 0.5);

            $incoming = $this->allowed($user, 'payments.read')
                ? (float) $this->paymentsQuery($filter, PaymentType::Incoming)->sum('amount')
                : 0.0;
            $outgoing = $this->allowed($user, 'payments.read')
                ? (float) $this->paymentsQuery($filter, PaymentType::Outgoing)->sum('amount')
                : 0.0;

            return [
                'revenue' => round($revenue, 2),
                'profit' => round($grossProfit, 2),
                'gross_profit' => round($grossProfit, 2),
                'net_profit' => round($netProfit, 2),
                'sales_today' => round($this->salesForDay($user, now()->toDateString()), 2),
                'sales_week' => round($this->salesBetween($user, now()->startOfWeek()->toDateString(), now()->toDateString()), 2),
                'sales_month' => round($this->salesBetween($user, now()->startOfMonth()->toDateString(), now()->toDateString()), 2),
                'expenses' => round($expenses, 2),
                'cash_flow' => round($incoming - $outgoing, 2),
                'outstanding_invoices' => round($this->allowed($user, 'invoices.read')
                    ? (float) Invoice::query()
                        ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])
                        ->when($filter->customerId, fn ($q) => $q->where('contact_id', $filter->customerId))
                        ->sum('total_amount')
                    : 0, 2),
                'outstanding_bills' => round($this->allowed($user, 'bills.read')
                    ? (float) Bill::query()
                        ->whereIn('status', [BillStatus::Received, BillStatus::Overdue])
                        ->when($filter->supplierId, fn ($q) => $q->where('contact_id', $filter->supplierId))
                        ->sum('total_amount')
                    : 0, 2),
                'payroll_cost' => round($this->allowed($user, 'payrolls.read')
                    ? (float) Payroll::query()
                        ->whereBetween('pay_period_start', [$filter->from->toDateString(), $filter->to->toDateString()])
                        ->sum('net_amount')
                    : 0, 2),
                'inventory_value' => round($this->allowed($user, 'products.read')
                    ? (float) Product::query()
                        ->selectRaw('COALESCE(SUM(stock_quantity * purchase_price), 0) as value')
                        ->value('value')
                    : 0, 2),
                'low_stock' => $this->allowed($user, 'products.read')
                    ? Product::query()->whereColumn('stock_quantity', '<=', 'minimum_stock_level')->count()
                    : 0,
                'open_leads' => $this->allowed($user, 'leads.read')
                    ? Lead::query()->whereIn('status', [LeadStatus::New, LeadStatus::Contacted, LeadStatus::Qualified])->count()
                    : 0,
                'open_tickets' => $this->allowed($user, 'tickets.read')
                    ? Ticket::query()->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress])->count()
                    : 0,
                'production_orders' => $this->allowed($user, 'production-orders.read')
                    ? ProductionOrder::query()->count()
                    : 0,
                'employees' => $this->allowed($user, 'employees.read') ? Employee::query()->count() : 0,
                'customers' => $this->allowed($user, 'contacts.read')
                    ? Contact::query()->whereIn('type', ['customer', 'both'])->count()
                    : 0,
            ];
        });
    }

    protected function invoiceRevenue(BiFilter $filter): float
    {
        return (float) Invoice::query()
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Paid, InvoiceStatus::Overdue])
            ->whereBetween('invoice_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->when($filter->customerId, fn ($q) => $q->where('contact_id', $filter->customerId))
            ->sum('total_amount');
    }

    protected function posRevenue(BiFilter $filter): float
    {
        return (float) PosSale::query()
            ->where('status', PosSaleStatus::Completed)
            ->where('is_return', false)
            ->whereBetween('created_at', [$filter->from, $filter->to])
            ->when($filter->customerId, fn ($q) => $q->where('contact_id', $filter->customerId))
            ->when($filter->branchId, function ($q) use ($filter) {
                $q->whereHas('register', fn ($r) => $r->where('branch_id', $filter->branchId));
            })
            ->sum('total_amount');
    }

    protected function salesForDay(User $user, string $day): float
    {
        if (! $this->allowed($user, 'invoices.read') && ! $this->allowed($user, 'pos.read')) {
            return 0.0;
        }

        $filter = BiFilter::fromArray(['from' => $day, 'to' => $day]);

        return $this->invoiceRevenue($filter) + $this->posRevenue($filter);
    }

    protected function salesBetween(User $user, string $from, string $to): float
    {
        if (! $this->allowed($user, 'invoices.read') && ! $this->allowed($user, 'pos.read')) {
            return 0.0;
        }

        $filter = BiFilter::fromArray(['from' => $from, 'to' => $to]);

        return $this->invoiceRevenue($filter) + $this->posRevenue($filter);
    }

    /**
     * @return Builder<Expense>
     */
    protected function expensesQuery(BiFilter $filter): Builder
    {
        return Expense::query()
            ->whereBetween('expense_date', [$filter->from->toDateString(), $filter->to->toDateString()]);
    }

    /**
     * @return Builder<PurchaseOrder>
     */
    protected function purchaseOrdersQuery(BiFilter $filter): Builder
    {
        return PurchaseOrder::query()
            ->whereBetween('order_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->when($filter->supplierId, fn ($q) => $q->where('contact_id', $filter->supplierId))
            ->when($filter->warehouseId, fn ($q) => $q->where('warehouse_id', $filter->warehouseId));
    }

    /**
     * @return Builder<Payment>
     */
    protected function paymentsQuery(BiFilter $filter, PaymentType $type): Builder
    {
        return Payment::query()
            ->where('type', $type)
            ->whereBetween('payment_date', [$filter->from->toDateString(), $filter->to->toDateString()]);
    }

    protected function allowed(?User $user, string $permission): bool
    {
        return $user !== null && $user->can($permission);
    }
}
