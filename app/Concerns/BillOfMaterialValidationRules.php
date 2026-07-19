<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait BillOfMaterialValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function billOfMaterialRules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'component_product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric'],
            'unit' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function billOfMaterialUpdateRules(?int $billOfMaterialId = null): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'component_product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric'],
            'unit' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
