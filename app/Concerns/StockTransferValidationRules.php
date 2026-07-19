<?php

namespace App\Concerns;

use App\Enums\StockTransferStatus;
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
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer'],
            'transfer_date' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(StockTransferStatus::class)],
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
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer'],
            'transfer_date' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(StockTransferStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
