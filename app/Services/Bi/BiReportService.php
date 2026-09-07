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
use App\Support\Bi\BiValueFormatter;
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
            'rows' => BiValueFormatter::listRows(
                collect($kpis)->map(fn ($v, $k) => [__('scf.bi.kpi_'.$k), $v])->values()->all(),
            ),
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
            'rows' => BiValueFormatter::listRows($invoices->map(fn (Invoice $i) => [
                $i->reference_number,
                $i->contact !== null ? $i->contact->name : null,
                BiValueFormatter::formatDate($i->invoice_date),
                BiValueFormatter::enumValue($i->status),
                (float) $i->total_amount,
            ])),
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
            'rows' => BiValueFormatter::listRows($products->map(fn (Product $p) => [
                $p->sku,
                $p->name,
                $p->category !== null ? $p->category->name : null,
                $p->stock_quantity,
                (float) $p->purchase_price,
                (float) $p->sale_price,
                round($p->stock_quantity * (float) $p->purchase_price, 2),
            ])),
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
            'rows' => BiValueFormatter::listRows($orders->map(fn (SaleOrder $o) => [
                $o->reference_number,
                $o->contact !== null ? $o->contact->name : null,
                $o->warehouse !== null ? $o->warehouse->name : null,
                BiValueFormatter::formatDate($o->order_date),
                BiValueFormatter::enumValue($o->status),
                (float) $o->total_amount,
            ])),
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
            'rows' => BiValueFormatter::listRows($orders->map(fn (PurchaseOrder $o) => [
                $o->reference_number,
                $o->contact !== null ? $o->contact->name : null,
                $o->warehouse !== null ? $o->warehouse->name : null,
                BiValueFormatter::formatDate($o->order_date),
                BiValueFormatter::enumValue($o->status),
                (float) $o->total_amount,
            ])),
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
            'rows' => BiValueFormatter::listRows($orders->map(fn (ProductionOrder $o) => [
                $o->reference_number ?? '#'.$o->id,
                BiValueFormatter::enumValue($o->status),
                BiValueFormatter::formatDate($o->created_at),
            ])),
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
            'rows' => BiValueFormatter::listRows($employees->map(fn (Employee $e) => [
                trim($e->first_name.' '.$e->last_name) ?: '#'.$e->id,
                $e->email ?? '—',
                $e->department ?? '—',
                $e->is_active ? __('Active') : __('Inactive'),
            ])),
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
            'rows' => BiValueFormatter::listRows($leads->map(fn (Lead $l) => [
                $l->name ?: ($l->company ?: '#'.$l->id),
                BiValueFormatter::enumValue($l->status),
                $l->source ?? '—',
                BiValueFormatter::formatDate($l->created_at),
            ])),
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
            'rows' => BiValueFormatter::listRows($tasks->map(fn (ProjectTask $t) => [
                $t->title ?? $t->name ?? '#'.$t->id,
                BiValueFormatter::enumValue($t->status),
                BiValueFormatter::formatDate($t->due_date) ?? '—',
                BiValueFormatter::formatDate($t->created_at),
            ])),
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
            'rows' => BiValueFormatter::listRows($branches->map(fn (Branch $b) => [
                $b->code,
                $b->name,
                $b->phone ?? '—',
                $b->is_active ? __('Yes') : __('No'),
            ])),
        ];
    }

    /**
     * @return Collection<int, array{key: string, label: string}>
     */
    public function availableReports(User $user): Collection
    {
        $reports = [];

        foreach (config('bi.reports') as $key => $permission) {
            if (! is_string($permission) || ! $user->can($permission)) {
                continue;
            }

            $reports[] = [
                'key' => (string) $key,
                'label' => (string) __('scf.bi.report_'.$key),
            ];
        }

        return collect($reports);
    }
}
