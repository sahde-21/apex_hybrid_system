<?php

namespace App\Services;

use App\Repositories\VehicleMaintenanceRepository;
use App\Models\VehicleMaintenance;

/**
 * @extends BaseService<VehicleMaintenance>
 */
class VehicleMaintenanceService extends BaseService
{
    public function __construct(VehicleMaintenanceRepository $repository)
    {
        parent::__construct($repository);
    }
}
