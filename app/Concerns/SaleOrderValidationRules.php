<?php

namespace App\Concerns;

use App\Enums\SaleOrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait SaleOrderValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function saleOrderRules(?int $saleOrderId = null): array
    {
        return [
            'reference_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sale_orders', 'reference_number')->ignore($saleOrderId),
            ],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'order_date' => ['required', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'status' => ['required', Rule::enum(SaleOrderStatus::class)],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
