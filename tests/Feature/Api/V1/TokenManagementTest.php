<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('api login rejects locked users', function () {
    $user = User::factory()->locked()->create([
        'password' => 'password',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertForbidden()->assertJsonPath('success', false);
});

test('authenticated api users can list create and revoke tokens', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/tokens')
        ->assertOk()
        ->assertJsonPath('success', true);

    $created = $this->postJson('/api/v1/tokens', [
        'name' => 'Mobile Device',
        'client' => 'flutter',
        'abilities' => ['read'],
    ])->assertCreated();

    $tokenId = $created->json('data.access_token.id');

    $this->getJson("/api/v1/tokens/{$tokenId}")
        ->assertOk()
        ->assertJsonPath('data.id', $tokenId);

    $this->deleteJson("/api/v1/tokens/{$tokenId}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($user->fresh()->tokens()->whereKey($tokenId)->exists())->toBeFalse();
});

test('api logout-all revokes every personal access token', function () {
    $user = User::factory()->create();
    $user->createToken('one');
    $user->createToken('two');
    $token = $user->createToken('current')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout-all')
        ->assertOk();

    expect($user->fresh()->tokens()->count())->toBe(0);
});

test('unauthenticated token endpoints are unauthorized', function () {
    $this->getJson('/api/v1/tokens')->assertUnauthorized();
    $this->postJson('/api/v1/tokens', ['name' => 'x'])->assertUnauthorized();
});
