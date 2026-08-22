<?php

use App\Models\PosRegister;
use App\Models\PosShift;
use App\Models\User;
use App\Support\Api\ApiAbilities;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function posShiftApiPayload(array $overrides = []): array
{
    return array_merge([
        'opening_amount' => 100,
        'opening_notes' => 'Start of day',
    ], $overrides);
}

function posShiftJsonKeys(): array
{
    return [
        'id',
        'pos_register_id',
        'user_id',
        'status' => ['value', 'label'],
        'opened_at',
        'closed_at',
        'opening_amount' => ['amount', 'currency_code'],
        'closing_amount',
        'opening_notes',
        'closing_notes',
        'created_at',
        'updated_at',
    ];
}

test('unauthenticated pos shift requests are blocked', function () {
    $shift = PosShift::factory()->create();

    $this->getJson('/api/v1/pos-shifts')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);

    $this->postJson('/api/v1/pos-shifts', posShiftApiPayload(['pos_register_id' => $shift->pos_register_id]))->assertUnauthorized();
    $this->getJson('/api/v1/pos-shifts/'.$shift->id)->assertUnauthorized();
    $this->putJson('/api/v1/pos-shifts/'.$shift->id, ['opening_notes' => 'Nope'])->assertUnauthorized();
    $this->deleteJson('/api/v1/pos-shifts/'.$shift->id)->assertUnauthorized();
});

test('inactive users cannot access pos shifts', function () {
    $user = User::factory()->inactive()->create();
    $user->givePermissionTo(['pos.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/pos-shifts')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('users without pos permissions are forbidden', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['employees.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/pos-shifts')
        ->assertForbidden()
        ->assertJsonPath('success', false);

    $this->postJson('/api/v1/pos-shifts', posShiftApiPayload(['pos_register_id' => 1]))
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('token ability enforcement blocks pos shifts when ability is missing', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['pos.read']);
    Sanctum::actingAs($user, [ApiAbilities::EMPLOYEES_READ]);

    $this->getJson('/api/v1/pos-shifts')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('pos shifts api supports authorized crud with a stable json envelope', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['pos.read', 'pos.create', 'pos.update', 'pos.delete']);
    Sanctum::actingAs($user, ['*']);

    $register = PosRegister::factory()->create();

    $created = $this->postJson('/api/v1/pos-shifts', posShiftApiPayload([
        'pos_register_id' => $register->id,
        'opening_amount' => 150.5,
    ]))
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', __('scf.api.pos_shifts.created'))
        ->assertJsonStructure([
            'success',
            'message',
            'data' => posShiftJsonKeys(),
            'meta' => ['version', 'timestamp'],
        ])
        ->json('data');

    expect($created)
        ->not->toHaveKey('sales')
        ->not->toHaveKey('register')
        ->not->toHaveKey('user')
        ->and($created['pos_register_id'])->toBe($register->id)
        ->and($created['user_id'])->toBe($user->id)
        ->and($created['status']['value'])->toBe('open')
        ->and($created['opening_amount']['amount'])->toBe('150.50')
        ->and($created['closing_amount'])->toBeNull();

    $this->getJson('/api/v1/pos-shifts/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status.value', 'open')
        ->assertJsonMissingPath('data.sales')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => posShiftJsonKeys(),
            'meta',
        ]);

    $this->getJson('/api/v1/pos-shifts')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [posShiftJsonKeys()],
            'meta' => [
                'pagination' => ['current_page', 'per_page', 'total', 'links'],
            ],
        ]);

    $this->putJson('/api/v1/pos-shifts/'.$created['id'], [
        'opening_notes' => 'Counted twice',
    ])
        ->assertOk()
        ->assertJsonPath('data.opening_notes', 'Counted twice')
        ->assertJsonPath('data.status.value', 'open')
        ->assertJsonStructure(['success', 'message', 'data' => posShiftJsonKeys(), 'meta']);

    $this->deleteJson('/api/v1/pos-shifts/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null)
        ->assertJsonStructure(['success', 'message', 'data', 'meta']);

    expect(PosShift::query()->whereKey($created['id'])->exists())->toBeFalse();
});

test('pos shift store validates required fields amounts and foreign keys', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['pos.create']);
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/pos-shifts', ['opening_notes' => 'Incomplete'])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);

    $this->postJson('/api/v1/pos-shifts', posShiftApiPayload([
        'pos_register_id' => 999999,
        'opening_amount' => -10,
    ]))
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['pos_register_id', 'opening_amount']);
});

test('pos shift conflicts return 409 instead of 500', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['pos.read', 'pos.create', 'pos.update', 'pos.delete']);
    Sanctum::actingAs($user, ['*']);

    $register = PosRegister::factory()->create();

    $opened = $this->postJson('/api/v1/pos-shifts', posShiftApiPayload([
        'pos_register_id' => $register->id,
    ]))
        ->assertCreated()
        ->json('data');

    $this->postJson('/api/v1/pos-shifts', posShiftApiPayload([
        'pos_register_id' => $register->id,
        'opening_amount' => 25,
    ]))
        ->assertStatus(409)
        ->assertJsonPath('success', false);

    $closed = $this->putJson('/api/v1/pos-shifts/'.$opened['id'], [
        'status' => 'closed',
        'closing_amount' => 100,
    ])
        ->assertOk()
        ->assertJsonPath('data.status.value', 'closed')
        ->json('data');

    expect($closed['closing_amount']['amount'])->toBe('100.00');

    $this->putJson('/api/v1/pos-shifts/'.$opened['id'], [
        'opening_notes' => 'Cannot edit closed shift',
    ])
        ->assertStatus(409)
        ->assertJsonPath('success', false);

    $this->deleteJson('/api/v1/pos-shifts/'.$opened['id'])
        ->assertStatus(409)
        ->assertJsonPath('success', false);
});
