<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\EmployeeValidationRules;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    use EmployeeValidationRules;

    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            && ($this->user()?->can('update', $employee) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');

        return $this->employeeRules($employee instanceof Employee ? $employee->id : null);
    }
}
