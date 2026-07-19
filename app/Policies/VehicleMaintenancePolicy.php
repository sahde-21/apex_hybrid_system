<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleMaintenance;

class VehicleMaintenancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vehicle-maintenance.read');
    }

    public function view(User $user, VehicleMaintenance $vehicleMaintenance): bool
    {
        return $user->can('vehicle-maintenance.read');
    }

    public function create(User $user): bool
    {
        return $user->can('vehicle-maintenance.create');
    }

    public function update(User $user, VehicleMaintenance $vehicleMaintenance): bool
    {
        return $user->can('vehicle-maintenance.update');
    }

    public function delete(User $user, VehicleMaintenance $vehicleMaintenance): bool
    {
        return $user->can('vehicle-maintenance.delete');
    }
}
