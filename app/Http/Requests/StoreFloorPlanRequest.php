<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFloorPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('floor-plans.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'width' => ['nullable', 'integer'],
            'height' => ['nullable', 'integer'],
            'layout_data' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
