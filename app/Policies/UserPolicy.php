<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.read');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.read') || $user->is($model);
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.update');
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $user->can('users.delete');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('users.approve');
    }

    public function forceDelete(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $user->can('users.delete') && $user->hasRole('super-admin');
    }

    public function assignRoles(User $user, User $model): bool
    {
        return $user->can('users.approve');
    }

    public function lock(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $user->can('users.approve');
    }

    public function unlock(User $user, User $model): bool
    {
        return $user->can('users.approve');
    }

    public function changePassword(User $user, User $model): bool
    {
        return $user->can('users.update') || $user->is($model);
    }

    public function forcePasswordReset(User $user, User $model): bool
    {
        return $user->can('users.approve');
    }
}
