<?php

namespace App\Concerns;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PurchaseOrderValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function purchaseOrderRules(?int $purchaseOrderId = null): array
    {
        return [
            'reference_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('purchase_orders', 'reference_number')->ignore($purchaseOrderId),
            ],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'status' => ['required', Rule::enum(PurchaseOrderStatus::class)],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
