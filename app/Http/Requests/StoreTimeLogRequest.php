<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('time-logs.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
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
