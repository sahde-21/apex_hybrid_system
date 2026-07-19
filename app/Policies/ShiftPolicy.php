<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shift-management.read');
    }

    public function view(User $user, Shift $shift): bool
    {
        return $user->can('shift-management.read');
    }

    public function create(User $user): bool
    {
        return $user->can('shift-management.create');
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->can('shift-management.update');
    }

    public function delete(User $user, Shift $shift): bool
    {
        return $user->can('shift-management.delete');
    }
}
