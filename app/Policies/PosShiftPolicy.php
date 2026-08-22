<?php

namespace App\Policies;

use App\Models\PosShift;
use App\Models\User;

class PosShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pos.read');
    }

    public function view(User $user, PosShift $posShift): bool
    {
        return $user->can('pos.read');
    }

    public function create(User $user): bool
    {
        return $user->can('pos.create');
    }

    public function update(User $user, PosShift $posShift): bool
    {
        return $user->can('pos.update');
    }

    public function delete(User $user, PosShift $posShift): bool
    {
        return $user->can('pos.delete');
    }
}
