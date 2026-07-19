<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('subscriptions.read');
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->can('subscriptions.read');
    }

    public function create(User $user): bool
    {
        return $user->can('subscriptions.create');
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->can('subscriptions.update');
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->can('subscriptions.delete');
    }
}
