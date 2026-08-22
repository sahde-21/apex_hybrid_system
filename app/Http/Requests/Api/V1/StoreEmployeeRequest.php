<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\EmployeeValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    use EmployeeValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('employees.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->employeeRules();
    }
}
