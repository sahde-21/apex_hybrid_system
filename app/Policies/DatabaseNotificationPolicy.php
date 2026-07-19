<?php

namespace App\Policies;

use App\Models\DatabaseNotification;
use App\Models\User;

class DatabaseNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notifications.read');
    }

    public function view(User $user, DatabaseNotification $notification): bool
    {
        return $user->can('notifications.read') && $this->owns($user, $notification);
    }

    public function update(User $user, DatabaseNotification $notification): bool
    {
        return $user->can('notifications.read') && $this->owns($user, $notification);
    }

    public function delete(User $user, DatabaseNotification $notification): bool
    {
        return $user->can('notifications.delete') && $this->owns($user, $notification);
    }

    protected function owns(User $user, DatabaseNotification $notification): bool
    {
        return $notification->notifiable_type === $user->getMorphClass()
            && (int) $notification->notifiable_id === (int) $user->getKey();
    }
}
