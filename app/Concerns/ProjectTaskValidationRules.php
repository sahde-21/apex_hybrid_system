<?php

namespace App\Concerns;

use App\Enums\ProjectTaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProjectTaskValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function projectTaskRules(): array
    {
        return [
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ProjectTaskStatus::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function projectTaskUpdateRules(?int $projectTaskId = null): array
    {
        return [
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ProjectTaskStatus::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
