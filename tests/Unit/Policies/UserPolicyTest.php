<?php

use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->policy = new UserPolicy;
});

it('allows users with users.read to viewAny', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('users.read');

    expect($this->policy->viewAny($actor))->toBeTrue();
});

it('allows a user to view their own profile without users.read', function () {
    $actor = User::factory()->create();

    expect($this->policy->view($actor, $actor))->toBeTrue();
});

it('prevents users from deleting themselves', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('users.delete');

    expect($this->policy->delete($actor, $actor))->toBeFalse();
});

it('allows delete of other users with users.delete', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('users.delete');
    $target = User::factory()->create();

    expect($this->policy->delete($actor, $target))->toBeTrue();
});

it('requires users.approve for assignRoles and lock', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create();

    expect($this->policy->assignRoles($actor, $target))->toBeFalse()
        ->and($this->policy->lock($actor, $target))->toBeFalse();

    $actor->givePermissionTo('users.approve');

    expect($this->policy->assignRoles($actor, $target))->toBeTrue()
        ->and($this->policy->lock($actor, $target))->toBeTrue();
});

it('prevents locking yourself', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('users.approve');

    expect($this->policy->lock($actor, $actor))->toBeFalse();
});
