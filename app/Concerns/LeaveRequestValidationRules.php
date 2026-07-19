<?php

namespace App\Concerns;

use App\Enums\LeaveRequestStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait LeaveRequestValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function leaveRequestRules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(LeaveRequestStatus::class)],
            'reason' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function leaveRequestUpdateRules(?int $leaveRequestId = null): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(LeaveRequestStatus::class)],
            'reason' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
