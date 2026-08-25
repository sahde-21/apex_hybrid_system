<?php

namespace App\Http\Requests;

use App\Enums\InventoryAdjustmentReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $adjustment = $this->route('inventoryAdjustment');

        return $this->user()?->can('update', $adjustment) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('inventoryAdjustment')?->id;

        return [
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('inventory_adjustments', 'reference_number')->ignore($id)],
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
