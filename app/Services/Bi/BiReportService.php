<?php

namespace App\Services\Bi;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProjectTask;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\User;
use App\Support\Bi\BiFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BiReportService
{
    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    public function report(User $user, string $type, BiFilter $filter): array
    {
        $permission = config('bi.reports.'.$type);
        abort_unless($permission && $user->can($permission), 403);

        return Cache::remember($filter->cacheKey('report:'.$type), config('bi.cache_ttl'), function () use ($user, $type, $filter) {
            return match ($type) {
                'executive' => $this->executive($user, $filter),
                'financial' => $this->financial($filter),
                'inventory' => $this->inventory(),
                'sales' => $this->sales($filter),
                'purchase' => $this->purchase($filter),
                'manufacturing' => $this->manufacturing($filter),
                'hr' => $this->hr(),
                'crm' => $this->crm(),
                'project' => $this->projects(),
                'branch' => $this->branches(),
                default => ['headers' => [], 'rows' => [], 'title' => __('scf.bi.reports')],
            };
        });
    }

    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    protected function executive(User $user, BiFilter $filter): array
    {
        $kpis = app(BiKpiService::class)->kpis($user, $filter);

        return [
            'title' => __('scf.bi.report_executive'),
            'headers' => [__('Metric'), __('Value')],
            'rows' => collect($kpis)->map(fn ($v, $k) => [__('scf.bi.kpi_'.$k), $v])->values()->all(),
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    protected function financial(BiFilter $filter): array
    {
        $invoices = Invoice::query()
            ->with('contact:id,name')
            ->whereBetween('invoice_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->latest('invoice_date')
            ->limit(200)
            ->get();

        return [
            'title' => __('scf.bi.report_financial'),
            'headers' => [__('Reference'), __('Customer'), __('Date'), __('Status'), __('Total')],
            'rows' => $invoices->map(fn (Invoice $i) => [
                $i->reference_number,
                $i->contact?->name,
                $i->invoice_date?->format('Y-m-d'),
                $i->status?->value,
                (float) $i->total_amount,
            ])->all(),
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    protected function inventory(): array
    {
        $products = Product::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->limit(500)
            ->get();

        return [
            'title' => __('scf.bi.report_inventory'),
            'headers' => [__('SKU'), __('Name'), __('Category'), __('Stock'), __('Purchase'), __('Sale'), __('Value')],
            'rows' => $products->map(fn (Product $p) => [
                $p->sku,
                $p->name,
                $p->category?->name,
                $p->stock_quantity,
                (float) $p->purchase_price,
                (float) $p->sale_price,
                round($p->stock_quantity * (float) $p->purchase_price, 2),
            ])->all(),
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    protected function sales(BiFilter $filter): array
    {
        $orders = SaleOrder::query()
            ->with(['contact:id,name', 'warehouse:id,name'])
            ->whereBetween('order_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->when($filter->customerId, fn ($q) => $q->where('contact_id', $filter->customerId))
            ->when($filter->warehouseId, fn ($q) => $q->where('warehouse_id', $filter->warehouseId))
            ->latest('order_date')
            ->limit(200)
            ->get();

        return [
            'title' => __('scf.bi.report_sales'),
            'headers' => [__('Reference'), __('Customer'), __('Warehouse'), __('Date'), __('Status'), __('Total')],
            'rows' => $orders->map(fn (SaleOrder $o) => [
                $o->reference_number,
                $o->contact?->name,
                $o->warehouse?->name,
                $o->order_date?->format('Y-m-d'),
                $o->status instanceof \BackedEnum ? $o->status->value : (string) $o->status,
                (float) $o->total_amount,
            ])->all(),
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    protected function purchase(BiFilter $filter): array
    {
        $orders = PurchaseOrder::query()
            ->with(['contact:id,name', 'warehouse:id,name'])
            ->whereBetween('order_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->when($filter->supplierId, fn ($q) => $q->where('contact_id', $filter->supplierId))
            ->latest('order_date')
            ->limit(200)
            ->get();

        return [
            'title' => __('scf.bi.report_purchase'),
            'headers' => [__('Reference'), __('Supplier'), __('Warehouse'), __('Date'), __('Status'), __('Total')],
            'rows' => $orders->map(fn (PurchaseOrder $o) => [
                $o->reference_number,
                $o->contact?->name,
                $o->warehouse?->name,
                $o->order_date?->format('Y-m-d'),
                $o->status instanceof \BackedEnum ? $o->status->value : (string) $o->status,
                (float) $o->total_amount,
            ])->all(),
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    protected function manufacturing(BiFilter $filter): array
    {
        $orders = ProductionOrder::query()
            ->latest()
            ->limit(200)
            ->get();

        return [
            'title' => __('scf.bi.report_manufacturing'),
            'headers' => [__('Reference'), __('Status'), __('Created')],
            'rows' => $orders->map(fn (ProductionOrder $o) => [
                $o->reference_number ?? '#'.$o->id,
                $o->status instanceof \BackedEnum ? $o->status->value : (string) ($o->status ?? '—'),
                $o->created_at?->format('Y-m-d'),
            ])->all(),
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    protected function hr(): array
    {
        $employees = Employee::query()->orderBy('last_name')->orderBy('first_name')->limit(300)->get();

        return [
            'title' => __('scf.bi.report_hr'),
            'headers' => [__('Name'), __('Email'), __('Department'), __('Status')],
            'rows' => $employees->map(fn (Employee $e) => [
                trim($e->first_name.' '.$e->last_name) ?: '#'.$e->id,
                $e->email ?? '—',
                $e->department ?? '—',
                $e->is_active ? __('Active') : __('Inactive'),
            ])->all(),
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    protected function crm(): array
    {
        $leads = Lead::query()->latest()->limit(200)->get();

        return [
            'title' => __('scf.bi.report_crm'),
            'headers' => [__('Name'), __('Status'), __('Source'), __('Created')],
            'rows' => $leads->map(fn (Lead $l) => [
                $l->name ?: ($l->company ?: '#'.$l->id),
                $l->status instanceof \BackedEnum ? $l->status->value : (string) $l->status,
                $l->source ?? '—',
                $l->created_at?->format('Y-m-d'),
            ])->all(),
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    protected function projects(): array
    {
        $tasks = ProjectTask::query()->latest()->limit(200)->get();

        return [
            'title' => __('scf.bi.report_project'),
            'headers' => [__('Title'), __('Status'), __('Due'), __('Created')],
            'rows' => $tasks->map(fn (ProjectTask $t) => [
                $t->title ?? $t->name ?? '#'.$t->id,
                $t->status instanceof \BackedEnum ? $t->status->value : (string) ($t->status ?? '—'),
                $t->due_date?->format('Y-m-d') ?? '—',
                $t->created_at?->format('Y-m-d'),
            ])->all(),
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<mixed>>, title: string}
     */
    protected function branches(): array
    {
        $branches = Branch::query()->orderBy('name')->get();

        return [
            'title' => __('scf.bi.report_branch'),
            'headers' => [__('Code'), __('Name'), __('Phone'), __('Active')],
            'rows' => $branches->map(fn (Branch $b) => [
                $b->code,
                $b->name,
                $b->phone ?? '—',
                $b->is_active ? __('Yes') : __('No'),
            ])->all(),
        ];
    }

    /**
     * @return Collection<int, array{key: string, label: string}>
     */
    public function availableReports(User $user): Collection
    {
        return collect(config('bi.reports'))
            ->filter(fn (string $permission) => $user->can($permission))
            ->map(fn (string $permission, string $key) => [
                'key' => $key,
                'label' => __('scf.bi.report_'.$key),
            ])
            ->values();
    }
}
