<?php

namespace App\Http\Requests;

use App\Enums\PerformanceReviewStatus;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerformanceReviewRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('performance-reviews.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('performanceReview');

        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'review_date' => ['required', 'date'],
            'rating' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(PerformanceReviewStatus::class)],
            'comments' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
