<?php

use App\Models\Branch;
use App\Models\PosRegister;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Api\ApiAbilities;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function posRegisterApiPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Front Counter',
        'code' => 'REG-API-1',
        'warehouse_id' => null,
        'branch_id' => null,
        'is_active' => true,
        'cash_drawer_enabled' => true,
        'notes' => 'Main till',
    ], $overrides);
}

function posRegisterJsonKeys(): array
{
    return [
        'id',
        'name',
        'code',
        'warehouse_id',
        'branch_id',
        'is_active',
        'cash_drawer_enabled',
        'notes',
        'created_at',
        'updated_at',
    ];
}

test('unauthenticated pos register requests are blocked', function () {
    $register = PosRegister::factory()->create();

    $this->getJson('/api/v1/pos-registers')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);

    $this->postJson('/api/v1/pos-registers', posRegisterApiPayload())->assertUnauthorized();
    $this->getJson('/api/v1/pos-registers/'.$register->id)->assertUnauthorized();
    $this->putJson('/api/v1/pos-registers/'.$register->id, posRegisterApiPayload())->assertUnauthorized();
    $this->deleteJson('/api/v1/pos-registers/'.$register->id)->assertUnauthorized();
});

test('inactive users cannot access pos registers', function () {
    $user = User::factory()->inactive()->create();
    $user->givePermissionTo(['pos.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/pos-registers')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('users without pos permissions are forbidden', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['employees.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/pos-registers')
        ->assertForbidden()
        ->assertJsonPath('success', false);

    $this->postJson('/api/v1/pos-registers', posRegisterApiPayload())
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('token ability enforcement blocks pos registers when ability is missing', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['pos.read']);
    Sanctum::actingAs($user, [ApiAbilities::EMPLOYEES_READ]);

    $this->getJson('/api/v1/pos-registers')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('pos registers api supports authorized crud with a stable json envelope', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['pos.read', 'pos.create', 'pos.update', 'pos.delete']);
    Sanctum::actingAs($user, ['*']);

    $warehouse = Warehouse::factory()->create();
    $branch = Branch::factory()->create();

    $created = $this->postJson('/api/v1/pos-registers', posRegisterApiPayload([
        'warehouse_id' => $warehouse->id,
        'branch_id' => $branch->id,
    ]))
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', __('scf.api.pos_registers.created'))
        ->assertJsonStructure([
            'success',
            'message',
            'data' => posRegisterJsonKeys(),
            'meta' => ['version', 'timestamp'],
        ])
        ->json('data');

    expect($created)
        ->not->toHaveKey('shifts')
        ->not->toHaveKey('warehouse')
        ->not->toHaveKey('branch')
        ->and($created['code'])->toBe('REG-API-1')
        ->and($created['warehouse_id'])->toBe($warehouse->id)
        ->and($created['branch_id'])->toBe($branch->id)
        ->and($created['is_active'])->toBeTrue();

    $this->getJson('/api/v1/pos-registers/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Front Counter')
        ->assertJsonMissingPath('data.shifts')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => posRegisterJsonKeys(),
            'meta',
        ]);

    $this->getJson('/api/v1/pos-registers')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [posRegisterJsonKeys()],
            'meta' => [
                'pagination' => ['current_page', 'per_page', 'total', 'links'],
            ],
        ]);

    $this->putJson('/api/v1/pos-registers/'.$created['id'], posRegisterApiPayload([
        'name' => 'Back Counter',
        'code' => 'REG-API-1',
        'warehouse_id' => $warehouse->id,
        'branch_id' => $branch->id,
        'is_active' => false,
        'cash_drawer_enabled' => false,
    ]))
        ->assertOk()
        ->assertJsonPath('data.name', 'Back Counter')
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.cash_drawer_enabled', false)
        ->assertJsonStructure(['success', 'message', 'data' => posRegisterJsonKeys(), 'meta']);

    $this->deleteJson('/api/v1/pos-registers/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null)
        ->assertJsonStructure(['success', 'message', 'data', 'meta']);

    expect(PosRegister::query()->whereKey($created['id'])->exists())->toBeFalse();
});

test('pos register store validates required fields and foreign keys', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['pos.create']);
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/pos-registers', ['name' => 'Incomplete'])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);

    $this->postJson('/api/v1/pos-registers', posRegisterApiPayload([
        'warehouse_id' => 999999,
        'branch_id' => 999999,
    ]))
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});
