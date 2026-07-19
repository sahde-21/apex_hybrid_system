<?php

namespace App\Concerns;

use App\Enums\PerformanceReviewStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PerformanceReviewValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function performanceReviewRules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'review_date' => ['required', 'date'],
            'rating' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(PerformanceReviewStatus::class)],
            'comments' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function performanceReviewUpdateRules(?int $performanceReviewId = null): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'review_date' => ['required', 'date'],
            'rating' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(PerformanceReviewStatus::class)],
            'comments' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
