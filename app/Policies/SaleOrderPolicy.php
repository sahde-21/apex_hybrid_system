<?php

namespace App\Policies;

use App\Models\SaleOrder;
use App\Models\User;

class SaleOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sale-orders.read');
    }

    public function view(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.read');
    }

    public function create(User $user): bool
    {
        return $user->can('sale-orders.create');
    }

    public function update(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.update');
    }

    public function delete(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.delete');
    }
}
