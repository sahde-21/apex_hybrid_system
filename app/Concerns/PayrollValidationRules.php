<?php

namespace App\Concerns;

use App\Enums\PayrollStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PayrollValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function payrollRules(?int $payrollId = null): array
    {
        return [
            'reference_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('payrolls', 'reference_number')->ignore($payrollId),
            ],
            'employee_id' => ['required', 'exists:employees,id'],
            'pay_period_start' => ['required', 'date'],
            'pay_period_end' => ['required', 'date', 'after_or_equal:pay_period_start'],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'deductions' => ['required', 'numeric', 'min:0'],
            'net_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(PayrollStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
