<?php

namespace App\Notifications\Channels;

use App\Notifications\ErpNotification;
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Notifications\Notification;

class EnterpriseDatabaseChannel extends BaseDatabaseChannel
{
    /**
     * @return array<string, mixed>
     */
    protected function buildPayload($notifiable, Notification $notification): array
    {
        $payload = parent::buildPayload($notifiable, $notification);

        if ($notification instanceof ErpNotification) {
            $payload['category'] = $notification->category->value;
            $payload['priority'] = $notification->priority->value;
            $payload['module'] = $notification->module;
        }

        return $payload;
    }
}
