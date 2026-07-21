<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('activities.read') || $user->can('activities.view_all');
    }

    public function view(User $user, Activity $activity): bool
    {
        if (! $user->can('activities.read') && ! $user->can('activities.view_all')) {
            return false;
        }

        if ($activity->visibility->value === 'internal'
            && ! $user->can('activities.internal_note')
            && ! $user->can('activities.manage')
            && ! $user->can('activities.view_all')) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('activities.comment') || $user->can('activities.create') || $user->can('activities.manage');
    }

    public function update(User $user, Activity $activity): bool
    {
        return $activity->isEditableBy($user);
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $activity->isDeletableBy($user);
    }
}
