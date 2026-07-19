<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('inactive users cannot authenticate', function () {
    $user = User::factory()->inactive()->create([
        'email' => 'inactive@example.com',
        'password' => 'password',
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('verified users with a role can reach the dashboard after login', function () {
    $user = User::factory()->create([
        'email' => 'cashier-login@example.com',
        'password' => 'password',
    ]);
    $user->assignRole('cashier');

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertOk();
});

test('guests are redirected away from protected settings pages', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->get(route('security.edit'))->assertRedirect(route('login'));
    $this->get(route('appearance.edit'))->assertRedirect(route('login'));
});
