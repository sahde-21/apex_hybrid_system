<?php

namespace App\Concerns;

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait FinancialReportValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function financialReportRules(?int $financialReportId = null): array
    {
        return [
            'reference_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('financial_reports', 'reference_number')->ignore($financialReportId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'report_type' => ['required', Rule::enum(FinancialReportType::class)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'status' => ['required', Rule::enum(FinancialReportStatus::class)],
            'total_revenue' => ['required', 'numeric', 'min:0'],
            'total_expenses' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
