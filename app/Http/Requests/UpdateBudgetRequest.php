<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('budgeting.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('budget');

        return [
            'name' => ['required', 'string', 'max:255'],
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('budgets', 'reference_number')->ignore($id)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date'],
            'allocated_amount' => ['required', 'numeric'],
            'spent_amount' => ['nullable', 'numeric'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
