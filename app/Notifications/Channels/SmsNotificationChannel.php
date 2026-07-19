<?php

namespace App\Notifications\Channels;

/**
 * Architecture stub — enable when SMS provider credentials are configured.
 */
class SmsNotificationChannel implements NotificationChannelContract
{
    public function key(): string
    {
        return 'sms';
    }

    public function enabled(): bool
    {
        return (bool) config('notifications.channels.sms', false);
    }

    public function send(object $notifiable, array $payload): void
    {
        // Future: integrate Twilio / local SMS gateway.
    }
}
