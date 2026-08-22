<?php

use App\Models\Branch;
use App\Models\User;
use App\Support\Api\ApiAbilities;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function branchApiPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Erbil HQ',
        'code' => 'BR-API-1',
        'address' => '100 Gulan Street',
        'phone' => '+964 750 100 2000',
        'email' => 'erbil@example.com',
        'is_active' => true,
    ], $overrides);
}

function branchJsonKeys(): array
{
    return [
        'id',
        'name',
        'code',
        'address',
        'phone',
        'email',
        'is_active',
        'created_at',
        'updated_at',
    ];
}

test('unauthenticated branch requests are blocked', function () {
    $branch = Branch::factory()->create();

    $this->getJson('/api/v1/branches')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);

    $this->postJson('/api/v1/branches', branchApiPayload())->assertUnauthorized();
    $this->getJson('/api/v1/branches/'.$branch->id)->assertUnauthorized();
    $this->putJson('/api/v1/branches/'.$branch->id, branchApiPayload())->assertUnauthorized();
    $this->deleteJson('/api/v1/branches/'.$branch->id)->assertUnauthorized();
});

test('inactive users cannot access branches', function () {
    $user = User::factory()->inactive()->create();
    $user->givePermissionTo(['branches.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/branches')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('users without branch permissions are forbidden', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['employees.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/branches')
        ->assertForbidden()
        ->assertJsonPath('success', false);

    $this->postJson('/api/v1/branches', branchApiPayload())
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('token ability enforcement blocks branches when ability is missing', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['branches.read']);
    Sanctum::actingAs($user, [ApiAbilities::EMPLOYEES_READ]);

    $this->getJson('/api/v1/branches')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('branches api supports authorized crud with a stable json envelope', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['branches.read', 'branches.create', 'branches.update', 'branches.delete']);
    Sanctum::actingAs($user, ['*']);

    $created = $this->postJson('/api/v1/branches', branchApiPayload())
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', __('scf.api.branches.created'))
        ->assertJsonStructure([
            'success',
            'message',
            'data' => branchJsonKeys(),
            'meta' => ['version', 'timestamp'],
        ])
        ->json('data');

    expect($created)
        ->not->toHaveKey('sale_orders')
        ->not->toHaveKey('purchase_orders')
        ->not->toHaveKey('pos_registers')
        ->not->toHaveKey('accounts')
        ->and($created['code'])->toBe('BR-API-1')
        ->and($created['email'])->toBe('erbil@example.com')
        ->and($created['is_active'])->toBeTrue();

    $this->getJson('/api/v1/branches/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Erbil HQ')
        ->assertJsonMissingPath('data.sale_orders')
        ->assertJsonMissingPath('data.pos_registers')
        ->assertJsonMissingPath('data.accounts')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => branchJsonKeys(),
            'meta',
        ]);

    $this->getJson('/api/v1/branches')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [branchJsonKeys()],
            'meta' => [
                'pagination' => ['current_page', 'per_page', 'total', 'links'],
            ],
        ]);

    $this->putJson('/api/v1/branches/'.$created['id'], branchApiPayload([
        'name' => 'Duhok Branch',
        'code' => 'BR-API-1',
        'email' => 'duhok@example.com',
        'is_active' => false,
    ]))
        ->assertOk()
        ->assertJsonPath('data.name', 'Duhok Branch')
        ->assertJsonPath('data.email', 'duhok@example.com')
        ->assertJsonPath('data.is_active', false)
        ->assertJsonStructure(['success', 'message', 'data' => branchJsonKeys(), 'meta']);

    $this->deleteJson('/api/v1/branches/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null)
        ->assertJsonStructure(['success', 'message', 'data', 'meta']);

    expect(Branch::query()->whereKey($created['id'])->exists())->toBeFalse();
});

test('branch store validates required fields unique code and contact info', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['branches.create']);
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/branches', ['name' => 'Incomplete'])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);

    $this->postJson('/api/v1/branches', branchApiPayload([
        'email' => 'not-an-email',
    ]))
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['email']);

    $this->postJson('/api/v1/branches', branchApiPayload())->assertCreated();

    $this->postJson('/api/v1/branches', branchApiPayload([
        'name' => 'Duplicate Code',
    ]))
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['code']);
});
