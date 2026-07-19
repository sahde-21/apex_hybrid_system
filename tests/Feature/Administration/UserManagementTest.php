<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create([
        'email' => 'users-admin-'.uniqid().'@scf.com',
    ]);
    $admin->syncRoles(['super-admin']);
    $this->actingAs($admin);
});

it('allows super-admin to visit users index create and show', function () {
    $user = User::factory()->create();

    $this->get(route('users.index'))->assertOk();
    $this->get(route('users.create'))->assertOk();
    $this->get(route('users.show', $user))->assertOk();
    $this->get(route('users.edit', $user))->assertOk();
});

it('creates a user with a role via livewire', function () {
    Livewire::test('pages::administration.users-create')
        ->set('name', 'New Operator')
        ->set('email', 'operator@example.com')
        ->set('password', 'Password1!')
        ->set('password_confirmation', 'Password1!')
        ->set('roles', ['sales'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'operator@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('sales'))->toBeTrue()
        ->and(Hash::check('Password1!', $user->password))->toBeTrue();
});

it('soft deletes restores locks and unlocks users', function () {
    $user = User::factory()->create();

    Livewire::test('pages::administration.users-index')
        ->call('confirmDelete', $user->id)
        ->call('deleteUser')
        ->assertHasNoErrors();

    expect(User::query()->find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull();

    Livewire::test('pages::administration.users-index')
        ->set('trashed', true)
        ->call('restoreUser', $user->id)
        ->assertHasNoErrors();

    expect(User::query()->find($user->id))->not->toBeNull();

    Livewire::test('pages::administration.users-index')
        ->call('toggleLock', $user->id)
        ->assertHasNoErrors();

    expect($user->fresh()->isLocked())->toBeTrue();

    Livewire::test('pages::administration.users-index')
        ->call('toggleLock', $user->id)
        ->assertHasNoErrors();

    expect($user->fresh()->isLocked())->toBeFalse();
});

it('denies locked users from authenticating', function () {
    auth()->logout();

    $user = User::factory()->locked()->create([
        'email' => 'locked@example.com',
        'password' => 'password',
    ]);

    $this->post(route('login.store'), [
        'email' => 'locked@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('forbids non-privileged users from viewing users module', function () {
    auth()->logout();

    $user = User::factory()->create();
    $user->syncRoles(['cashier']);

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertForbidden();
});
