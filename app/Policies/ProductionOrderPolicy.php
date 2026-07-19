<?php

namespace App\Policies;

use App\Models\ProductionOrder;
use App\Models\User;

class ProductionOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('production-orders.read');
    }

    public function view(User $user, ProductionOrder $productionOrder): bool
    {
        return $user->can('production-orders.read');
    }

    public function create(User $user): bool
    {
        return $user->can('production-orders.create');
    }

    public function update(User $user, ProductionOrder $productionOrder): bool
    {
        return $user->can('production-orders.update');
    }

    public function delete(User $user, ProductionOrder $productionOrder): bool
    {
        return $user->can('production-orders.delete');
    }
}
