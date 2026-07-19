<?php

namespace App\Notifications\Channels;

/**
 * Channel architecture contracts for future email / SMS / push delivery.
 * Database delivery is handled by Laravel's built-in channel today.
 */
interface NotificationChannelContract
{
    public function key(): string;

    public function enabled(): bool;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(object $notifiable, array $payload): void;
}
