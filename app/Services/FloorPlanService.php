<?php

namespace App\Services;

use App\Repositories\FloorPlanRepository;

class FloorPlanService extends BaseService
{
    public function __construct(FloorPlanRepository $repository)
    {
        parent::__construct($repository);
    }
}
