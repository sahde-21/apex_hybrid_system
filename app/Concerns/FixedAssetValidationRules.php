<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait FixedAssetValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function fixedAssetRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'asset_code' => ['required', 'string', 'max:255', Rule::unique('fixed_assets', 'asset_code')],
            'purchase_date' => ['required', 'date'],
            'purchase_cost' => ['required', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function fixedAssetUpdateRules(?int $fixedAssetId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'asset_code' => ['required', 'string', 'max:255', Rule::unique('fixed_assets', 'asset_code')->ignore($fixedAssetId)],
            'purchase_date' => ['required', 'date'],
            'purchase_cost' => ['required', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
