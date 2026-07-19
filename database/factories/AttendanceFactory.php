<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'branch_id' => Branch::factory(),
            'attendance_date' => fake()->date(),
            'check_in' => '09:00:00',
            'check_out' => '09:00:00',
            'status' => fake()->word(),
            'notes' => fake()->paragraph(),
        ];
    }
}
