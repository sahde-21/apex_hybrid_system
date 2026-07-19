<?php

namespace Database\Factories;

use App\Enums\ProjectTaskStatus;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\ProjectTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTask>
 */
class ProjectTaskFactory extends Factory
{
    protected $model = ProjectTask::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'employee_id' => Employee::factory(),
            'title' => fake()->words(3, true),
            'due_date' => fake()->date(),
            'priority' => 'medium',
            'status' => fake()->randomElement(array_column(ProjectTaskStatus::cases(), 'value')),
            'description' => fake()->paragraph(),
        ];
    }
}
