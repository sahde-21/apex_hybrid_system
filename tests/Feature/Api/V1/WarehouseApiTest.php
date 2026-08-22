<?php

use App\Models\User;
use App\Models\Warehouse;
use App\Support\Api\ApiAbilities;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function warehousePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Main Depot',
        'code' => 'WH-API-1',
        'address' => '100 Warehouse Rd',
        'phone' => '+1 555-1000',
        'is_active' => true,
    ], $overrides);
}

function warehouseJsonKeys(): array
{
    return ['id', 'name', 'code', 'address', 'phone', 'is_active', 'created_at', 'updated_at'];
}

test('unauthenticated warehouse requests are blocked', function () {
    $warehouse = Warehouse::factory()->create();

    $this->getJson('/api/v1/warehouses')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);

    $this->postJson('/api/v1/warehouses', warehousePayload())->assertUnauthorized();
    $this->getJson('/api/v1/warehouses/'.$warehouse->id)->assertUnauthorized();
    $this->putJson('/api/v1/warehouses/'.$warehouse->id, warehousePayload())->assertUnauthorized();
    $this->deleteJson('/api/v1/warehouses/'.$warehouse->id)->assertUnauthorized();
});

test('inactive users cannot access warehouses', function () {
    $user = User::factory()->inactive()->create();
    $user->givePermissionTo(['warehouses.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/warehouses')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('users without warehouse permissions are forbidden', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['products.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/warehouses')
        ->assertForbidden()
        ->assertJsonPath('success', false);

    $this->postJson('/api/v1/warehouses', warehousePayload())
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('token ability enforcement blocks warehouses when ability is missing', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['warehouses.read']);
    Sanctum::actingAs($user, [ApiAbilities::PRODUCTS_READ]);

    $this->getJson('/api/v1/warehouses')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('warehouses api supports authorized crud with a stable json envelope', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['warehouses.read', 'warehouses.create', 'warehouses.update', 'warehouses.delete']);
    Sanctum::actingAs($user, ['*']);

    $created = $this->postJson('/api/v1/warehouses', warehousePayload())
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', __('scf.api.warehouses.created'))
        ->assertJsonStructure([
            'success',
            'message',
            'data' => warehouseJsonKeys(),
            'meta' => ['version', 'timestamp'],
        ])
        ->json('data');

    expect($created)
        ->not->toHaveKey('branch_id')
        ->and($created['code'])->toBe('WH-API-1')
        ->and($created['is_active'])->toBeTrue();

    $this->getJson('/api/v1/warehouses/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Main Depot')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => warehouseJsonKeys(),
            'meta',
        ]);

    $this->getJson('/api/v1/warehouses')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [warehouseJsonKeys()],
            'meta' => [
                'pagination' => ['current_page', 'per_page', 'total', 'links'],
            ],
        ]);

    $this->putJson('/api/v1/warehouses/'.$created['id'], warehousePayload([
        'name' => 'North Depot',
        'code' => 'WH-API-1',
        'is_active' => false,
    ]))
        ->assertOk()
        ->assertJsonPath('data.name', 'North Depot')
        ->assertJsonPath('data.is_active', false)
        ->assertJsonStructure(['success', 'message', 'data' => warehouseJsonKeys(), 'meta']);

    $this->deleteJson('/api/v1/warehouses/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null)
        ->assertJsonStructure(['success', 'message', 'data', 'meta']);

    expect(Warehouse::query()->whereKey($created['id'])->exists())->toBeFalse();
});

test('warehouse store validates required fields', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['warehouses.create']);
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/warehouses', ['name' => 'Incomplete'])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);
});
