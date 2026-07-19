<?php

namespace App\Notifications\Channels;

/**
 * Architecture stub — enable when mail templates and queues are production-ready.
 */
class MailNotificationChannel implements NotificationChannelContract
{
    public function key(): string
    {
        return 'mail';
    }

    public function enabled(): bool
    {
        return (bool) config('notifications.channels.mail', false);
    }

    public function send(object $notifiable, array $payload): void
    {
        // Future: dispatch Mailable / NotificationTemplate-backed email.
    }
}
