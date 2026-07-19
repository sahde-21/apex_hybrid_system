<?php

namespace App\Policies;

use App\Models\FloorPlan;
use App\Models\User;

class FloorPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('floor-plans.read');
    }

    public function view(User $user, FloorPlan $floorPlan): bool
    {
        return $user->can('floor-plans.read');
    }

    public function create(User $user): bool
    {
        return $user->can('floor-plans.create');
    }

    public function update(User $user, FloorPlan $floorPlan): bool
    {
        return $user->can('floor-plans.update');
    }

    public function delete(User $user, FloorPlan $floorPlan): bool
    {
        return $user->can('floor-plans.delete');
    }
}
