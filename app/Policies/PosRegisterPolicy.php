<?php

namespace App\Policies;

use App\Models\PosRegister;
use App\Models\User;

class PosRegisterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pos.read');
    }

    public function view(User $user, PosRegister $posRegister): bool
    {
        return $user->can('pos.read');
    }

    public function create(User $user): bool
    {
        return $user->can('pos.create');
    }

    public function update(User $user, PosRegister $posRegister): bool
    {
        return $user->can('pos.update');
    }

    public function delete(User $user, PosRegister $posRegister): bool
    {
        return $user->can('pos.delete');
    }
}
