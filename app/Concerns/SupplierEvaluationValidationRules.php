<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait SupplierEvaluationValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function supplierEvaluationRules(): array
    {
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'evaluation_date' => ['required', 'date'],
            'quality_score' => ['nullable', 'integer'],
            'delivery_score' => ['nullable', 'integer'],
            'price_score' => ['nullable', 'integer'],
            'overall_score' => ['nullable', 'integer'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function supplierEvaluationUpdateRules(?int $supplierEvaluationId = null): array
    {
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'evaluation_date' => ['required', 'date'],
            'quality_score' => ['nullable', 'integer'],
            'delivery_score' => ['nullable', 'integer'],
            'price_score' => ['nullable', 'integer'],
            'overall_score' => ['nullable', 'integer'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
