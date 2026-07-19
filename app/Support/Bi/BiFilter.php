<?php

namespace App\Support\Bi;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class BiFilter
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public ?int $branchId = null,
        public ?int $warehouseId = null,
        public ?int $customerId = null,
        public ?int $supplierId = null,
        public ?int $employeeId = null,
        public ?string $department = null,
        public ?int $categoryId = null,
        public string $currency = 'IQD',
        public string $dashboard = 'ceo',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfDay();

        return new self(
            from: CarbonImmutable::parse($from)->startOfDay(),
            to: CarbonImmutable::parse($to)->endOfDay(),
            branchId: $request->integer('branch_id') ?: null,
            warehouseId: $request->integer('warehouse_id') ?: null,
            customerId: $request->integer('customer_id') ?: null,
            supplierId: $request->integer('supplier_id') ?: null,
            employeeId: $request->integer('employee_id') ?: null,
            department: $request->string('department')->toString() ?: null,
            categoryId: $request->integer('category_id') ?: null,
            currency: $request->string('currency', 'IQD')->toString() ?: 'IQD',
            dashboard: $request->string('dashboard', 'ceo')->toString() ?: 'ceo',
        );
    }

    /**
     * @param  array{
     *     from?: string|null,
     *     to?: string|null,
     *     branch_id?: int|null,
     *     warehouse_id?: int|null,
     *     customer_id?: int|null,
     *     supplier_id?: int|null,
     *     employee_id?: int|null,
     *     department?: string|null,
     *     category_id?: int|null,
     *     currency?: string|null,
     *     dashboard?: string|null
     * }  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            from: CarbonImmutable::parse($input['from'] ?? now()->startOfMonth())->startOfDay(),
            to: CarbonImmutable::parse($input['to'] ?? now()->endOfDay())->endOfDay(),
            branchId: isset($input['branch_id']) ? (int) $input['branch_id'] ?: null : null,
            warehouseId: isset($input['warehouse_id']) ? (int) $input['warehouse_id'] ?: null : null,
            customerId: isset($input['customer_id']) ? (int) $input['customer_id'] ?: null : null,
            supplierId: isset($input['supplier_id']) ? (int) $input['supplier_id'] ?: null : null,
            employeeId: isset($input['employee_id']) ? (int) $input['employee_id'] ?: null : null,
            department: $input['department'] ?? null,
            categoryId: isset($input['category_id']) ? (int) $input['category_id'] ?: null : null,
            currency: (string) ($input['currency'] ?? 'IQD'),
            dashboard: (string) ($input['dashboard'] ?? 'ceo'),
        );
    }

    public function cacheKey(string $suffix = ''): string
    {
        return config('bi.cache_prefix').md5(json_encode([
            $this->from->toDateString(),
            $this->to->toDateString(),
            $this->branchId,
            $this->warehouseId,
            $this->customerId,
            $this->supplierId,
            $this->employeeId,
            $this->department,
            $this->categoryId,
            $this->currency,
            $this->dashboard,
            $suffix,
            auth()->id(),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'branch_id' => $this->branchId,
            'warehouse_id' => $this->warehouseId,
            'customer_id' => $this->customerId,
            'supplier_id' => $this->supplierId,
            'employee_id' => $this->employeeId,
            'department' => $this->department,
            'category_id' => $this->categoryId,
            'currency' => $this->currency,
            'dashboard' => $this->dashboard,
        ];
    }
}
