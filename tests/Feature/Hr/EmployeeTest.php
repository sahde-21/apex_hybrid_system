<?php

use App\Models\Employee;
use Livewire\Livewire;

beforeEach(fn () => actingAsSuperAdmin());

test('employees index is displayed', function () {
    Employee::factory()->count(2)->create();

    $this->get(route('employees.index'))->assertOk();
});

test('employee can be stored via controller', function () {
    $this->post(route('employees.store'), [
        'employee_number' => 'EMP-TEST-001',
        'first_name' => 'Alex',
        'last_name' => 'Rivera',
        'email' => 'alex.rivera@example.com',
        'phone' => '+1 555-3000',
        'department' => 'Operations',
        'job_title' => 'Coordinator',
        'hire_date' => now()->toDateString(),
        'salary' => 4500,
        'is_active' => true,
    ])->assertRedirect(route('employees.index'));

    expect(Employee::query()->where('employee_number', 'EMP-TEST-001')->exists())->toBeTrue();
});

test('employee can be updated and deleted', function () {
    $employee = Employee::factory()->create([
        'first_name' => 'Sam',
        'last_name' => 'Lee',
    ]);

    $this->put(route('employees.update', $employee), [
        'employee_number' => $employee->employee_number,
        'first_name' => 'Samantha',
        'last_name' => 'Lee',
        'email' => $employee->email,
        'phone' => $employee->phone,
        'department' => 'HR',
        'job_title' => 'Specialist',
        'hire_date' => $employee->hire_date->toDateString(),
        'salary' => 5200,
        'is_active' => true,
    ])->assertRedirect(route('employees.index'));

    expect($employee->fresh()->first_name)->toBe('Samantha');

    Livewire::test('pages::hr.employees-index')
        ->call('confirmDelete', $employee->id)
        ->call('deleteEmployee')
        ->assertHasNoErrors();

    expect(Employee::query()->find($employee->id))->toBeNull();
});

test('hr role can manage employees and is forbidden from sales', function () {
    actingAsRole('hr');

    $this->get(route('employees.index'))->assertOk();
    $this->get(route('payrolls.index'))->assertOk();
    $this->get(route('sale-orders.index'))->assertForbidden();
});
