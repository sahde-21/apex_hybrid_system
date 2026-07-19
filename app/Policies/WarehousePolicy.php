<?php

namespace App\Policies;

use App\Models\Warehouse;
use App\Models\User;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('warehouses.read');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.read');
    }

    public function create(User $user): bool
    {
        return $user->can('warehouses.create');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.update');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.delete');
    }
}
