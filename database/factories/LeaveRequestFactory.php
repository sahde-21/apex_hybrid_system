<?php

namespace Database\Factories;

use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'leave_type' => 'general',
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'status' => LeaveRequestStatus::Draft,
            'reason' => fake()->paragraph(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => LeaveRequestStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => LeaveRequestStatus::Approved]);
    }
}
