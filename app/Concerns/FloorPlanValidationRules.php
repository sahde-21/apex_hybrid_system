<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait FloorPlanValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function floorPlanRules(): array
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

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function floorPlanUpdateRules(?int $floorPlanId = null): array
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
