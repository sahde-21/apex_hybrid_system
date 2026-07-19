<?php

namespace App\Http\Requests;

use App\Enums\ProjectTaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('project-tasks.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('projectTask')?->id;

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
