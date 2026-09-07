<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeLogRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('time-logs.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('timeLog');

        return [
            'project_task_id' => ['required', 'exists:project_tasks,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'log_date' => ['required', 'date'],
            'hours' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
