<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\ProjectTask;
use App\Models\TimeLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeLog>
 */
class TimeLogFactory extends Factory
{
    protected $model = TimeLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_task_id' => ProjectTask::factory(),
            'employee_id' => Employee::factory(),
            'log_date' => fake()->date(),
            'hours' => fake()->randomFloat(2, 0, 10000),
            'description' => fake()->paragraph(),
        ];
    }
}
