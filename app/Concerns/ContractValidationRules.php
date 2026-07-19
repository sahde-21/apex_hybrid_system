<?php

namespace App\Concerns;

use App\Enums\ContractStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ContractValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function contractRules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('contracts', 'reference_number')],
            'contact_id' => ['required', 'exists:contacts,id'],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'value' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(ContractStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function contractUpdateRules(?int $contractId = null): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('contracts', 'reference_number')->ignore($contractId)],
            'contact_id' => ['required', 'exists:contacts,id'],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'value' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(ContractStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
