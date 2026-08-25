<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait StockTransferValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function stockTransferRules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('stock_transfers', 'reference_number')],
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:variants,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function stockTransferUpdateRules(?int $stockTransferId = null): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('stock_transfers', 'reference_number')->ignore($stockTransferId)],
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:variants,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
