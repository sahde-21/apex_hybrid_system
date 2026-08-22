<?php

use App\Models\Employee;
use App\Models\ProjectTask;
use App\Models\TimeLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('exposes belongs-to and has-many relations for project tasks', function () {
    $task = new ProjectTask;

    expect($task->contract())->toBeInstanceOf(BelongsTo::class)
        ->and($task->employee())->toBeInstanceOf(BelongsTo::class)
        ->and($task->timeLogs())->toBeInstanceOf(HasMany::class);
});

it('loads inverse time logs through project task relations', function () {
    $employee = Employee::factory()->create();
    $task = ProjectTask::factory()->create(['employee_id' => $employee->id]);
    TimeLog::factory()->create([
        'project_task_id' => $task->id,
        'employee_id' => $employee->id,
    ]);

    $task->load(['employee', 'timeLogs']);

    expect($task->employee->is($employee))->toBeTrue()
        ->and($task->timeLogs)->toHaveCount(1)
        ->and($task->timeLogs->first()->project_task_id)->toBe($task->id);
});
