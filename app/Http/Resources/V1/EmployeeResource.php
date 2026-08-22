<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsApiValues;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Employee */
class EmployeeResource extends JsonResource
{
    use FormatsApiValues;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'department' => $this->department,
            'job_title' => $this->job_title,
            'hire_date' => $this->dateOnly($this->hire_date),
            'salary' => $this->money($this->salary),
            'is_active' => $this->is_active,
            'created_at' => $this->isoDate($this->created_at),
            'updated_at' => $this->isoDate($this->updated_at),
        ];
    }
}
