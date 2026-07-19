<?php

namespace App\Http\Requests;

use App\Enums\PerformanceReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('performance-reviews.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
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
