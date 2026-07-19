<?php

use App\Models\DatabaseNotification;
use App\Models\User;
use App\Policies\DatabaseNotificationPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;

beforeEach(fn () => test()->seed(RolePermissionSeeder::class));

test('database notification policy viewAny requires notifications.read', function () {
    $policy = new DatabaseNotificationPolicy;

    $allowed = User::factory()->create();
    $allowed->syncPermissions(['notifications.read']);

    $denied = User::factory()->create();
    $denied->syncPermissions(['dashboard.read']);

    expect($policy->viewAny($allowed))->toBeTrue()
        ->and($policy->viewAny($denied))->toBeFalse();
});

test('database notification policy ownership checks', function () {
    $policy = new DatabaseNotificationPolicy;

    $alice = User::factory()->create();
    $alice->syncPermissions(['notifications.read', 'notifications.delete']);

    $bob = User::factory()->create();

    $notification = new DatabaseNotification([
        'id' => (string) Str::uuid(),
        'type' => App\Notifications\ErpNotification::class,
        'notifiable_type' => $bob->getMorphClass(),
        'notifiable_id' => $bob->id,
        'data' => ['title' => 'x'],
    ]);

    expect($policy->view($alice, $notification))->toBeFalse()
        ->and($policy->delete($alice, $notification))->toBeFalse();

    $notification->notifiable_type = $alice->getMorphClass();
    $notification->notifiable_id = $alice->id;

    expect($policy->view($alice, $notification))->toBeTrue()
        ->and($policy->delete($alice, $notification))->toBeTrue();
});
