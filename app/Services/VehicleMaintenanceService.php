<?php

namespace App\Services;

use App\Repositories\VehicleMaintenanceRepository;

class VehicleMaintenanceService extends BaseService
{
    public function __construct(VehicleMaintenanceRepository $repository)
    {
        parent::__construct($repository);
    }
}
