<?php

namespace App\Concerns;

use App\Enums\InventoryAdjustmentReason;
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
            'variant_id' => ['nullable', 'exists:variants,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'adjustment_date' => ['required', 'date'],
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', Rule::enum(InventoryAdjustmentReason::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
