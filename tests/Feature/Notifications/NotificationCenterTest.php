<?php

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Models\DatabaseNotification;
use App\Models\SaleOrder;
use App\Models\User;
use App\Services\Notifications\NotificationCenterService;
use Livewire\Livewire;

beforeEach(function () {
    config(['notifications.domain_enabled' => false]);
});

test('users can open notification center with permission', function () {
    actingAsSuperAdmin();

    $this->get(route('notifications.index'))->assertOk();
});

test('users without notifications permission cannot open notification center', function () {
    $user = actingAsUserWithPermissions(['dashboard.read']);

    $this->get(route('notifications.index'))->assertForbidden();
});

test('notification center service delivers database notifications to recipients only', function () {
    actingAsSuperAdmin();
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    app(NotificationCenterService::class)->notify(
        $alice,
        event: 'test.event',
        title: 'Hello Alice',
        body: 'Private message',
        category: NotificationCategory::Success,
        priority: NotificationPriority::High,
        module: 'sale-orders',
    );

    expect($alice->notifications()->count())->toBe(1)
        ->and($bob->notifications()->count())->toBe(0)
        ->and($alice->unreadNotifications()->count())->toBe(1);

    $notification = $alice->notifications()->first();
    expect($notification)->toBeInstanceOf(DatabaseNotification::class)
        ->and($notification->title())->toBe('Hello Alice')
        ->and($notification->category)->toBe(NotificationCategory::Success)
        ->and($notification->priority)->toBe(NotificationPriority::High)
        ->and($notification->module)->toBe('sale-orders');
});

test('users can only mark read and delete their own notifications', function () {
    $alice = actingAsUserWithPermissions(['notifications.read', 'notifications.delete']);
    $bob = User::factory()->create();

    app(NotificationCenterService::class)->notify($bob, 'x', 'Bob only');
    app(NotificationCenterService::class)->notify($alice, 'y', 'Alice note');

    $bobNotification = $bob->notifications()->first();
    $aliceNotification = $alice->notifications()->first();

    expect(fn () => app(NotificationCenterService::class)->markAsRead($alice, $bobNotification->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    app(NotificationCenterService::class)->markAsRead($alice, $aliceNotification->id);
    expect($aliceNotification->fresh()->read_at)->not->toBeNull();

    app(NotificationCenterService::class)->delete($alice, $aliceNotification->id);
    expect($alice->notifications()->count())->toBe(0);
});

test('notification center livewire can filter mark all read and delete', function () {
    $user = actingAsSuperAdmin();

    $center = app(NotificationCenterService::class);
    $center->notify($user, 'a', 'First', category: NotificationCategory::Warning, priority: NotificationPriority::Low, module: 'tickets');
    $center->notify($user, 'b', 'Second', category: NotificationCategory::Critical, priority: NotificationPriority::Critical, module: 'invoices');

    Livewire::actingAs($user)
        ->test('pages::administration.notifications-index')
        ->assertSee('First')
        ->assertSee('Second')
        ->set('search', 'Second')
        ->assertSee('Second')
        ->assertDontSee('First')
        ->set('search', '')
        ->call('markAllAsRead')
        ->assertHasNoErrors();

    expect($user->unreadNotifications()->count())->toBe(0);

    $id = $user->notifications()->first()->id;

    Livewire::actingAs($user)
        ->test('pages::administration.notifications-index')
        ->call('deleteNotification', $id)
        ->assertHasNoErrors();

    expect($user->notifications()->count())->toBe(1);
});

test('domain observer emits notifications when enabled', function () {
    config(['notifications.domain_enabled' => true]);

    // Re-register observer for this test process.
    SaleOrder::observe(\App\Observers\DomainNotificationObserver::class);

    $user = actingAsSuperAdmin();

    $order = SaleOrder::factory()->create([
        'contact_id' => \App\Models\Contact::factory()->customer()->create()->id,
    ]);

    expect(
        $user->notifications()->where('module', 'sale-orders')->where('data->meta->id', $order->id)->exists()
            || $user->notifications()->where('module', 'sale-orders')->count() >= 1
    )->toBeTrue();
});

test('database notification policy enforces ownership', function () {
    $alice = actingAsUserWithPermissions(['notifications.read', 'notifications.delete']);
    $bob = User::factory()->create();

    app(NotificationCenterService::class)->notify($bob, 'x', 'Secret');
    $notification = $bob->notifications()->first();

    expect($alice->can('view', $notification))->toBeFalse()
        ->and($alice->can('delete', $notification))->toBeFalse();

    app(NotificationCenterService::class)->notify($alice, 'y', 'Mine');
    $own = $alice->notifications()->first();

    expect($alice->can('view', $own))->toBeTrue()
        ->and($alice->can('delete', $own))->toBeTrue();
});
