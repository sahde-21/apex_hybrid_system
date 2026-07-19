<?php

use App\Models\User;

it('builds initials from the user name', function () {
    $user = User::factory()->make(['name' => 'Sahdi Faisal']);

    expect($user->initials())->toBe('SF');
});

it('detects locked and inactive authentication state', function () {
    $active = User::factory()->make([
        'is_active' => true,
        'locked_at' => null,
    ]);

    $locked = User::factory()->locked()->make();
    $inactive = User::factory()->inactive()->make();

    expect($active->canAuthenticate())->toBeTrue()
        ->and($locked->isLocked())->toBeTrue()
        ->and($locked->canAuthenticate())->toBeFalse()
        ->and($inactive->canAuthenticate())->toBeFalse();
});

it('returns null avatar url without avatar path', function () {
    $user = User::factory()->make(['avatar_path' => null]);

    expect($user->avatarUrl())->toBeNull();
});
