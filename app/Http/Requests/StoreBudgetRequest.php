<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('budgeting.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('budgets', 'reference_number')],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date'],
            'allocated_amount' => ['required', 'numeric'],
            'spent_amount' => ['nullable', 'numeric'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
