<?php

namespace App\Policies;

use App\Models\NotificationTemplate;
use App\Models\User;

class NotificationTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notification-templates.read');
    }

    public function view(User $user, NotificationTemplate $notificationTemplate): bool
    {
        return $user->can('notification-templates.read');
    }

    public function create(User $user): bool
    {
        return $user->can('notification-templates.create');
    }

    public function update(User $user, NotificationTemplate $notificationTemplate): bool
    {
        return $user->can('notification-templates.update');
    }

    public function delete(User $user, NotificationTemplate $notificationTemplate): bool
    {
        return $user->can('notification-templates.delete');
    }
}
