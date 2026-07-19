<?php

namespace App\Http\Requests;

use App\Enums\PayrollStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payrolls.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('payrolls', 'reference_number')],
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
