<?php

namespace App\Http\Requests;

use App\Enums\SaleOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sale-orders.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('saleOrder')?->id;

        return [
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('sale_orders', 'reference_number')->ignore($id)],
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
