<?php

namespace App\Concerns;

use App\Enums\PaymentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PaymentValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function paymentRules(?int $paymentId = null): array
    {
        return [
            'reference_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('payments', 'reference_number')->ignore($paymentId),
            ],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'type' => ['required', Rule::enum(PaymentType::class)],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
