<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockTransferRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        $transfer = $this->route('stockTransfer');

        return $this->user()?->can('update', $transfer) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('stockTransfer');

        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('stock_transfers', 'reference_number')->ignore($id)],
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
