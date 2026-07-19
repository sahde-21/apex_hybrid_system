<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait VariantValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function variantRules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('variants', 'sku')],
            'barcode' => ['nullable', 'string', 'max:255'],
            'sale_price' => ['nullable', 'numeric'],
            'purchase_price' => ['nullable', 'numeric'],
            'stock_quantity' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function variantUpdateRules(?int $variantId = null): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('variants', 'sku')->ignore($variantId)],
            'barcode' => ['nullable', 'string', 'max:255'],
            'sale_price' => ['nullable', 'numeric'],
            'purchase_price' => ['nullable', 'numeric'],
            'stock_quantity' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
