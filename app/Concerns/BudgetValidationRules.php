<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait BudgetValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function budgetRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('budgets', 'reference_number')],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date'],
            'allocated_amount' => ['required', 'numeric'],
            'spent_amount' => ['nullable', 'numeric'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function budgetUpdateRules(?int $budgetId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('budgets', 'reference_number')->ignore($budgetId)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date'],
            'allocated_amount' => ['required', 'numeric'],
            'spent_amount' => ['nullable', 'numeric'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
