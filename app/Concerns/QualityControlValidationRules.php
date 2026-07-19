<?php

namespace App\Concerns;

use App\Enums\QualityControlStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait QualityControlValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function qualityControlRules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('quality_controls', 'reference_number')],
            'production_order_id' => ['nullable', 'exists:production_orders,id'],
            'product_id' => ['required', 'exists:products,id'],
            'inspection_date' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(QualityControlStatus::class)],
            'passed_quantity' => ['nullable', 'integer'],
            'failed_quantity' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function qualityControlUpdateRules(?int $qualityControlId = null): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('quality_controls', 'reference_number')->ignore($qualityControlId)],
            'production_order_id' => ['nullable', 'exists:production_orders,id'],
            'product_id' => ['required', 'exists:products,id'],
            'inspection_date' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(QualityControlStatus::class)],
            'passed_quantity' => ['nullable', 'integer'],
            'failed_quantity' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
