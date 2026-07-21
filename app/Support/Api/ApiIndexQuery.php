<?php

namespace App\Support\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiIndexQuery
{
    /**
     * @param  list<string>  $sortable
     * @param  list<string>  $searchable
     * @param  list<string>  $includes
     */
    public function __construct(
        protected Builder $query,
        protected array $sortable = ['id', 'created_at', 'updated_at'],
        protected array $searchable = [],
        protected array $includes = [],
    ) {}

    public function apply(Request $request): Builder
    {
        $this->applySearch($request);
        $this->applyFilters($request);
        $this->applySorting($request);
        $this->applyIncludes($request);

        return $this->query;
    }

    protected function applySearch(Request $request): void
    {
        $term = trim((string) $request->query('search', ''));

        if ($term === '' || $this->searchable === []) {
            return;
        }

        $this->query->where(function (Builder $builder) use ($term): void {
            foreach ($this->searchable as $column) {
                $builder->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }

    protected function applyFilters(Request $request): void
    {
        if ($request->filled('status')) {
            $this->query->where('status', $request->query('status'));
        }

        if ($request->filled('customer_id')) {
            $this->query->where('contact_id', $request->integer('customer_id'));
        }

        if ($request->filled('supplier_id')) {
            $this->query->where('contact_id', $request->integer('supplier_id'));
        }

        if ($request->filled('contact_id')) {
            $this->query->where('contact_id', $request->integer('contact_id'));
        }

        if ($request->filled('branch_id')) {
            $this->query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('warehouse_id')) {
            $this->query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('currency_code')) {
            $this->query->where('currency_code', $request->string('currency_code')->toString());
        }

        if ($request->filled('created_by')) {
            $this->query->where('created_by', $request->integer('created_by'));
        }

        foreach (['created_from', 'updated_from'] as $fromKey) {
            if ($request->filled($fromKey)) {
                $column = str_starts_with($fromKey, 'created') ? 'created_at' : 'updated_at';
                $this->query->whereDate($column, '>=', $request->date($fromKey));
            }
        }

        foreach (['created_to', 'updated_to'] as $toKey) {
            if ($request->filled($toKey)) {
                $column = str_starts_with($toKey, 'created') ? 'created_at' : 'updated_at';
                $this->query->whereDate($column, '<=', $request->date($toKey));
            }
        }

        foreach (['date_from', 'date_to'] as $dateFilter) {
            if (! $request->filled($dateFilter)) {
                continue;
            }

            $dateColumn = $this->resolveDateColumn($request);

            if (str_ends_with($dateFilter, '_from')) {
                $this->query->whereDate($dateColumn, '>=', $request->date($dateFilter));
            } else {
                $this->query->whereDate($dateColumn, '<=', $request->date($dateFilter));
            }
        }

        if ($request->filled('type')) {
            $this->query->where('type', $request->query('type'));
        }

        if ($request->boolean('is_active')) {
            $this->query->where('is_active', true);
        }
    }

    protected function applySorting(Request $request): void
    {
        $sort = (string) $request->query('sort', '-created_at');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (! in_array($column, $this->sortable, true)) {
            $column = 'created_at';
            $direction = 'desc';
        }

        $this->query->orderBy($column, $direction);
    }

    protected function applyIncludes(Request $request): void
    {
        $requested = collect(explode(',', (string) $request->query('include', '')))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->intersect($this->includes)
            ->values()
            ->all();

        if ($requested !== []) {
            $this->query->with($requested);
        }
    }

    protected function resolveDateColumn(Request $request): string
    {
        $allowed = array_intersect($this->sortable, [
            'quotation_date', 'order_date', 'invoice_date', 'payment_date',
            'request_date', 'rfq_date', 'bill_date', 'due_date', 'issued_at',
        ]);

        $column = (string) $request->query('date_field', 'created_at');

        return in_array($column, $allowed, true) ? $column : 'created_at';
    }
}
