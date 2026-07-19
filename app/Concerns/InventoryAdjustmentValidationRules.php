<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait InventoryAdjustmentValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function inventoryAdjustmentRules(?int $inventoryAdjustmentId = null): array
    {
        return [
            'reference_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('inventory_adjustments', 'reference_number')->ignore($inventoryAdjustmentId),
            ],
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'adjustment_date' => ['required', 'date'],
            'quantity_change' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
