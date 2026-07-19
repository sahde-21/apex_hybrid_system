<?php

namespace App\Policies;

use App\Models\PosSale;
use App\Models\User;

class PosSalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pos.read');
    }

    public function view(User $user, PosSale $posSale): bool
    {
        return $user->can('pos.read');
    }

    public function create(User $user): bool
    {
        return $user->can('pos.create');
    }

    public function update(User $user, PosSale $posSale): bool
    {
        return $user->can('pos.update');
    }

    public function delete(User $user, PosSale $posSale): bool
    {
        return $user->can('pos.delete');
    }

    public function print(User $user, PosSale $posSale): bool
    {
        return $user->can('pos.print');
    }

    public function export(User $user): bool
    {
        return $user->can('pos.export');
    }

    public function approve(User $user, PosSale $posSale): bool
    {
        return $user->can('pos.approve');
    }

    public function refund(User $user, PosSale $posSale): bool
    {
        return $user->can('pos.create') || $user->can('pos.update');
    }

    public function openShift(User $user): bool
    {
        return $user->can('pos.create');
    }

    public function closeShift(User $user): bool
    {
        return $user->can('pos.update') || $user->can('pos.create');
    }
}
