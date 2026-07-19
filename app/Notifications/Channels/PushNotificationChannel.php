<?php

namespace App\Notifications\Channels;

/**
 * Architecture stub — enable when FCM / APNs credentials are configured.
 */
class PushNotificationChannel implements NotificationChannelContract
{
    public function key(): string
    {
        return 'push';
    }

    public function enabled(): bool
    {
        return (bool) config('notifications.channels.push', false);
    }

    public function send(object $notifiable, array $payload): void
    {
        // Future: push to mobile / PWA subscribers.
    }
}
