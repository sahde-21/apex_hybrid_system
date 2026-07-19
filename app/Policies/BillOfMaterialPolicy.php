<?php

namespace App\Policies;

use App\Models\BillOfMaterial;
use App\Models\User;

class BillOfMaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bill-of-materials.read');
    }

    public function view(User $user, BillOfMaterial $billOfMaterial): bool
    {
        return $user->can('bill-of-materials.read');
    }

    public function create(User $user): bool
    {
        return $user->can('bill-of-materials.create');
    }

    public function update(User $user, BillOfMaterial $billOfMaterial): bool
    {
        return $user->can('bill-of-materials.update');
    }

    public function delete(User $user, BillOfMaterial $billOfMaterial): bool
    {
        return $user->can('bill-of-materials.delete');
    }
}
