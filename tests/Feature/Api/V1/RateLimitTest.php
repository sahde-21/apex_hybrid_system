<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('api auth endpoint is rate limited', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertStatus(429)
        ->assertJsonPath('success', false);
});

test('authenticated api read endpoints include version metadata', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('products.read');
    Sanctum::actingAs($user, ['products.read']);

    $this->getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonPath('meta.version', 'v1')
        ->assertHeader('X-Api-Version', 'v1');
});
