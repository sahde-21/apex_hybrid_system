<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PerformanceReview;
use App\Models\ProjectTask;
use App\Models\TimeLog;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('exposes has-many relations for every employee foreign key', function () {
    $employee = new Employee;

    expect($employee->attendances())->toBeInstanceOf(HasMany::class)
        ->and($employee->leaveRequests())->toBeInstanceOf(HasMany::class)
        ->and($employee->payrolls())->toBeInstanceOf(HasMany::class)
        ->and($employee->performanceReviews())->toBeInstanceOf(HasMany::class)
        ->and($employee->projectTasks())->toBeInstanceOf(HasMany::class)
        ->and($employee->timeLogs())->toBeInstanceOf(HasMany::class);
});

it('loads inverse records through employee relations', function () {
    $employee = Employee::factory()->create();

    Attendance::factory()->create(['employee_id' => $employee->id]);
    LeaveRequest::factory()->create(['employee_id' => $employee->id]);
    Payroll::factory()->create(['employee_id' => $employee->id]);
    PerformanceReview::factory()->create(['employee_id' => $employee->id]);
    $task = ProjectTask::factory()->create(['employee_id' => $employee->id]);
    TimeLog::factory()->create([
        'employee_id' => $employee->id,
        'project_task_id' => $task->id,
    ]);

    $employee->load([
        'attendances',
        'leaveRequests',
        'payrolls',
        'performanceReviews',
        'projectTasks',
        'timeLogs',
    ]);

    expect($employee->attendances)->toHaveCount(1)
        ->and($employee->leaveRequests)->toHaveCount(1)
        ->and($employee->payrolls)->toHaveCount(1)
        ->and($employee->performanceReviews)->toHaveCount(1)
        ->and($employee->projectTasks)->toHaveCount(1)
        ->and($employee->timeLogs)->toHaveCount(1);
});
