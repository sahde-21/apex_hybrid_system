<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\WarehouseValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    use WarehouseValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('warehouses.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->warehouseRules();
    }
}
