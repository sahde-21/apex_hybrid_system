<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('api health endpoint is available', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.version', 'v1');
});

test('api documentation endpoint is available', function () {
    $this->getJson('/api/v1/docs')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.openapi', '3.0.3');
});

test('users can authenticate via the api and receive a token', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'client' => 'flutter',
        'device_name' => 'Pixel Test',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'token_type',
                'user' => ['id', 'name', 'email'],
            ],
            'meta',
        ]);

    expect($user->tokens()->count())->toBe(1);
});

test('api login rejects invalid credentials with a standard error payload', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors']);
});

test('inactive users cannot authenticate via the api', function () {
    $user = User::factory()->inactive()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('authenticated users can fetch their profile and manage tokens', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);

    $created = $this->postJson('/api/v1/tokens', [
        'name' => 'Integration',
        'client' => 'integration',
        'abilities' => ['read'],
    ])->assertCreated();

    $tokenId = $created->json('data.access_token.id');

    $this->getJson('/api/v1/tokens')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['meta' => ['pagination']]);

    $this->getJson("/api/v1/tokens/{$tokenId}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Integration [integration]');

    $this->deleteJson("/api/v1/tokens/{$tokenId}")
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('users can logout and revoke the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('logout-test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($user->fresh()->tokens()->count())->toBe(0);
});

test('unauthenticated api requests receive a standard json error', function () {
    $this->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthenticated.');
});
