<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => actingAsSuperAdmin());

test('super-admin can create a user via http controller', function () {
    $this->post(route('users.store'), [
        'name' => 'HTTP User',
        'email' => 'http-user@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'is_active' => true,
        'roles' => ['sales'],
        'permissions' => [],
    ])->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'http-user@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('sales'))->toBeTrue()
        ->and(Hash::check('Password1!', $user->password))->toBeTrue();
});

test('super-admin can update a user via http controller', function () {
    $user = User::factory()->create(['name' => 'Before']);

    $this->put(route('users.update', $user), [
        'name' => 'After',
        'email' => $user->email,
        'phone' => '555-0100',
        'is_active' => true,
        'roles' => ['cashier'],
        'permissions' => [],
    ])->assertRedirect(route('users.index'));

    expect($user->fresh()->name)->toBe('After')
        ->and($user->fresh()->hasRole('cashier'))->toBeTrue();
});

test('super-admin can soft delete a user via http controller', function () {
    $user = User::factory()->create();

    $this->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    expect(User::query()->find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull();
});

test('users cannot delete themselves via http controller', function () {
    $user = actingAsUserWithPermissions(['users.read', 'users.delete']);

    $this->delete(route('users.destroy', $user))->assertForbidden();
});
