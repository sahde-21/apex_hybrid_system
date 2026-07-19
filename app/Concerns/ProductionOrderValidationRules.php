<?php

namespace App\Concerns;

use App\Enums\ProductionOrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProductionOrderValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function productionOrderRules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('production_orders', 'reference_number')],
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'quantity' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(ProductionOrderStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function productionOrderUpdateRules(?int $productionOrderId = null): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('production_orders', 'reference_number')->ignore($productionOrderId)],
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'quantity' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(ProductionOrderStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
