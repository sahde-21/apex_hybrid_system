<?php

namespace App\Services;

use App\Repositories\FloorPlanRepository;
use App\Models\FloorPlan;

/**
 * @extends BaseService<FloorPlan>
 */
class FloorPlanService extends BaseService
{
    public function __construct(FloorPlanRepository $repository)
    {
        parent::__construct($repository);
    }
}
