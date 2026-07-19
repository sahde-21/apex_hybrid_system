<?php

namespace App\Concerns;

use App\Enums\BankReconciliationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait BankReconciliationValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function bankReconciliationRules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('bank_reconciliations', 'reference_number')],
            'bank_name' => ['required', 'string', 'max:255'],
            'statement_date' => ['required', 'date'],
            'opening_balance' => ['nullable', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(BankReconciliationStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function bankReconciliationUpdateRules(?int $bankReconciliationId = null): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('bank_reconciliations', 'reference_number')->ignore($bankReconciliationId)],
            'bank_name' => ['required', 'string', 'max:255'],
            'statement_date' => ['required', 'date'],
            'opening_balance' => ['nullable', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(BankReconciliationStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
