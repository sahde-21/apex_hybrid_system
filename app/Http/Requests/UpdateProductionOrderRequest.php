<?php

namespace App\Http\Requests;

use App\Enums\ProductionOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('production-orders.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('productionOrder')?->id;

        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('production_orders', 'reference_number')->ignore($id)],
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
