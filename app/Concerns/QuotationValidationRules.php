<?php

namespace App\Concerns;

use App\Enums\QuotationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait QuotationValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function quotationRules(?int $quotationId = null): array
    {
        return [
            'reference_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('quotations', 'reference_number')->ignore($quotationId),
            ],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'status' => ['required', Rule::enum(QuotationStatus::class)],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
