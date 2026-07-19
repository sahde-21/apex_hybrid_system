<?php

use App\Models\ManagedDocument;
use App\Models\User;
use App\Policies\ManagedDocumentPolicy;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => test()->seed(RolePermissionSeeder::class));

test('managed document policy allows owners to view their documents', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('documents.read');

    $document = ManagedDocument::factory()->for($user, 'owner')->make();

    $policy = new ManagedDocumentPolicy;

    expect($policy->view($user, $document))->toBeTrue();
});

test('managed document policy denies users without permission', function () {
    $user = User::factory()->create();
    $document = ManagedDocument::factory()->make();

    $policy = new ManagedDocumentPolicy;

    expect($policy->view($user, $document))->toBeFalse();
});

test('only elevated roles can force delete documents', function () {
    $owner = User::factory()->create();
    $owner->syncRoles(['owner']);
    $owner->givePermissionTo('documents.delete');

    $staff = User::factory()->create();
    $staff->givePermissionTo('documents.delete');

    $document = ManagedDocument::factory()->make();
    $policy = new ManagedDocumentPolicy;

    expect($policy->forceDelete($owner, $document))->toBeTrue()
        ->and($policy->forceDelete($staff, $document))->toBeFalse();
});
