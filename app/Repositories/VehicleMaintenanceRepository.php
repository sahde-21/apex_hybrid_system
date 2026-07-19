<?php

namespace App\Repositories;

use App\Models\VehicleMaintenance;

/**
 * @extends BaseRepository<VehicleMaintenance>
 */
class VehicleMaintenanceRepository extends BaseRepository
{
    protected string $model = VehicleMaintenance::class;
}
