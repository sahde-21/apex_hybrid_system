<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\User;
use App\Support\Api\ApiAbilities;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function accountApiPayload(array $overrides = []): array
{
    return array_merge([
        'code' => '1000',
        'name' => 'Cash on Hand',
        'parent_id' => null,
        'type' => 'asset',
        'normal_balance' => 'debit',
        'currency_code' => 'IQD',
        'branch_id' => null,
        'is_active' => true,
        'allow_manual_entry' => true,
        'opening_balance' => 0,
        'description' => 'Till cash',
    ], $overrides);
}

function accountJsonKeys(): array
{
    return [
        'id',
        'code',
        'name',
        'parent_id',
        'type' => ['value', 'label'],
        'normal_balance' => ['value', 'label'],
        'currency_code',
        'branch_id',
        'is_active',
        'is_system',
        'allow_manual_entry',
        'system_key',
        'description',
        'opening_balance' => ['amount', 'currency_code'],
        'created_at',
        'updated_at',
    ];
}

test('unauthenticated account requests are blocked', function () {
    $account = Account::factory()->create();

    $this->getJson('/api/v1/accounts')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);

    $this->postJson('/api/v1/accounts', accountApiPayload())->assertUnauthorized();
    $this->getJson('/api/v1/accounts/'.$account->id)->assertUnauthorized();
    $this->putJson('/api/v1/accounts/'.$account->id, accountApiPayload())->assertUnauthorized();
    $this->deleteJson('/api/v1/accounts/'.$account->id)->assertUnauthorized();
});

test('inactive users cannot access accounts', function () {
    $user = User::factory()->inactive()->create();
    $user->givePermissionTo(['chart-of-accounts.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/accounts')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('users without chart of accounts permissions are forbidden', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['employees.read']);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/accounts')
        ->assertForbidden()
        ->assertJsonPath('success', false);

    $this->postJson('/api/v1/accounts', accountApiPayload())
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('token ability enforcement blocks accounts when ability is missing', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['chart-of-accounts.read']);
    Sanctum::actingAs($user, [ApiAbilities::EMPLOYEES_READ]);

    $this->getJson('/api/v1/accounts')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('accounts api supports authorized crud with a stable json envelope', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        'chart-of-accounts.read',
        'chart-of-accounts.create',
        'chart-of-accounts.update',
        'chart-of-accounts.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    $parent = Account::factory()->create(['code' => '100', 'name' => 'Current Assets']);
    $branch = Branch::factory()->create();

    $created = $this->postJson('/api/v1/accounts', accountApiPayload([
        'parent_id' => $parent->id,
        'branch_id' => $branch->id,
        'opening_balance' => 250.5,
    ]))
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', __('scf.api.accounts.created'))
        ->assertJsonStructure([
            'success',
            'message',
            'data' => accountJsonKeys(),
            'meta' => ['version', 'timestamp'],
        ])
        ->json('data');

    expect($created)
        ->not->toHaveKey('parent')
        ->not->toHaveKey('children')
        ->not->toHaveKey('lines')
        ->not->toHaveKey('branch')
        ->and($created['code'])->toBe('1000')
        ->and($created['parent_id'])->toBe($parent->id)
        ->and($created['branch_id'])->toBe($branch->id)
        ->and($created['type']['value'])->toBe('asset')
        ->and($created['is_active'])->toBeTrue()
        ->and($created['is_system'])->toBeFalse()
        ->and($created['opening_balance']['amount'])->toBe('250.50')
        ->and($created['opening_balance']['currency_code'])->toBe('IQD');

    $this->getJson('/api/v1/accounts/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Cash on Hand')
        ->assertJsonMissingPath('data.parent')
        ->assertJsonMissingPath('data.children')
        ->assertJsonMissingPath('data.lines')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => accountJsonKeys(),
            'meta',
        ]);

    $this->getJson('/api/v1/accounts')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [accountJsonKeys()],
            'meta' => [
                'pagination' => ['current_page', 'per_page', 'total', 'links'],
            ],
        ]);

    $this->putJson('/api/v1/accounts/'.$created['id'], accountApiPayload([
        'code' => '1000',
        'name' => 'Petty Cash',
        'parent_id' => $parent->id,
        'branch_id' => $branch->id,
        'is_active' => false,
        'allow_manual_entry' => false,
        'opening_balance' => 100,
    ]))
        ->assertOk()
        ->assertJsonPath('data.name', 'Petty Cash')
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.allow_manual_entry', false)
        ->assertJsonStructure(['success', 'message', 'data' => accountJsonKeys(), 'meta']);

    $this->deleteJson('/api/v1/accounts/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null)
        ->assertJsonStructure(['success', 'message', 'data', 'meta']);

    expect(Account::query()->whereKey($created['id'])->exists())->toBeFalse();
});

test('account store and update validate required fields and self-referencing parent_id', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        'chart-of-accounts.create',
        'chart-of-accounts.update',
    ]);
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/accounts', ['name' => 'Incomplete'])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors', 'meta']);

    $this->postJson('/api/v1/accounts', accountApiPayload([
        'type' => 'not-a-ledger-type',
        'parent_id' => 999999,
        'branch_id' => 999999,
    ]))
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    $created = $this->postJson('/api/v1/accounts', accountApiPayload(['code' => '1100']))
        ->assertCreated()
        ->json('data');

    $this->putJson('/api/v1/accounts/'.$created['id'], accountApiPayload([
        'code' => '1100',
        'parent_id' => $created['id'],
    ]))
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['parent_id']);
});
