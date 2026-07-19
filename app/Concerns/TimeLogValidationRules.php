<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait TimeLogValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function timeLogRules(): array
    {
        return [
            'project_task_id' => ['required', 'exists:project_tasks,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'log_date' => ['required', 'date'],
            'hours' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function timeLogUpdateRules(?int $timeLogId = null): array
    {
        return [
            'project_task_id' => ['required', 'exists:project_tasks,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'log_date' => ['required', 'date'],
            'hours' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
