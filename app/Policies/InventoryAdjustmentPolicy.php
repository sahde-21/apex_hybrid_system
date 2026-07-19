<?php

namespace App\Policies;

use App\Models\InventoryAdjustment;
use App\Models\User;

class InventoryAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory-adjustments.read');
    }

    public function view(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->can('inventory-adjustments.read');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory-adjustments.create');
    }

    public function update(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->can('inventory-adjustments.update');
    }

    public function delete(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->can('inventory-adjustments.delete');
    }
}
