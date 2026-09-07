<?php

namespace App\Http\Requests;

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportType;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialReportRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('financial-reports.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('financialReport');

        return [
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('financial_reports', 'reference_number')->ignore($id)],
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
