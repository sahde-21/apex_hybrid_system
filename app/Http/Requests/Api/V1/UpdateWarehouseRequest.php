<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\WarehouseValidationRules;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    use WarehouseValidationRules;

    public function authorize(): bool
    {
        $warehouse = $this->route('warehouse');

        return $warehouse instanceof Warehouse
            && ($this->user()?->can('update', $warehouse) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $warehouse = $this->route('warehouse');

        return $this->warehouseRules($warehouse instanceof Warehouse ? $warehouse->id : null);
    }
}
