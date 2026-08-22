<?php

use App\Models\Employee;
use App\Models\User;
use App\Support\Api\ApiAbilities;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function employeeApiPayload(array $overrides = []): array
{
    return array_merge([
        'employee_number' => 'EMP-API-001',
        'first_name' => 'Alex',
        'last_name' => 'Rivera',
        'email' => 'alex.rivera@example.com',
        'phone' => '+1 555-3000',
        'department' => 'Operations',
        'job_title' => 'Coordinator',
        'hire_date' => '2024-01-15',
        'salary' => 4500,
        'is_active' => true,
    ], $overrides);
}

function employeeJsonKeys(): array
{
    return [
        'id',
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'department',
        'job_title',
        'hire_date',
        'salary' => ['amount', 'currency_code'],
        'is_active',
        'created_at',
        'updated_at',
    ];
}

test('unauthenticated employee requests are blocked', function () {
    $employee = Employee::factory()->create();

    $this->getJson('/api/v1/employees')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);

    $this->postJson('/api/v1/employees', employeeApiPayload())->assertUnauthorized();
    $this->getJson('/api/v1/employees/'.$employee->id)->assertUnauthorized();
    $this->putJson('/api/v1/employees/'.$employee->id, employeeApiPayload())->assertUnauthorized();
    $this->deleteJson('/api/v1/employees/'.$employee->id)->assertUnauthorized();
});

test('inactive users cannot access employees', function () {
    $user = User::factory()->inactive()->create();
    $user->givePermissionTo(['employees.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/employees')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('users without employee permissions are forbidden', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['warehouses.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/employees')
        ->assertForbidden()
        ->assertJsonPath('success', false);

    $this->postJson('/api/v1/employees', employeeApiPayload())
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('token ability enforcement blocks employees when ability is missing', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['employees.read']);
    Sanctum::actingAs($user, [ApiAbilities::WAREHOUSES_READ]);

    $this->getJson('/api/v1/employees')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('employees api supports authorized crud with a stable json envelope', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['employees.read', 'employees.create', 'employees.update', 'employees.delete']);
    Sanctum::actingAs($user, ['*']);

    $created = $this->postJson('/api/v1/employees', employeeApiPayload())
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', __('scf.api.employees.created'))
        ->assertJsonStructure([
            'success',
            'message',
            'data' => employeeJsonKeys(),
            'meta' => ['version', 'timestamp'],
        ])
        ->json('data');

    expect($created)
        ->not->toHaveKey('attendances')
        ->not->toHaveKey('payrolls')
        ->and($created['employee_number'])->toBe('EMP-API-001')
        ->and($created['hire_date'])->toBe('2024-01-15')
        ->and($created['salary']['amount'])->toBe('4500.00')
        ->and($created['is_active'])->toBeTrue();

    $this->getJson('/api/v1/employees/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.first_name', 'Alex')
        ->assertJsonMissingPath('data.attendances')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => employeeJsonKeys(),
            'meta',
        ]);

    $this->getJson('/api/v1/employees')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [employeeJsonKeys()],
            'meta' => [
                'pagination' => ['current_page', 'per_page', 'total', 'links'],
            ],
        ]);

    $this->putJson('/api/v1/employees/'.$created['id'], employeeApiPayload([
        'first_name' => 'Samantha',
        'salary' => 5200,
        'is_active' => false,
    ]))
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Samantha')
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.salary.amount', '5200.00')
        ->assertJsonStructure(['success', 'message', 'data' => employeeJsonKeys(), 'meta']);

    $this->deleteJson('/api/v1/employees/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null)
        ->assertJsonStructure(['success', 'message', 'data', 'meta']);

    expect(Employee::query()->whereKey($created['id'])->exists())->toBeFalse();
});

test('employee store validates required fields', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['employees.create']);
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/employees', ['first_name' => 'Incomplete'])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);
});
