<?php

namespace App\Repositories;

use App\Models\FloorPlan;

/**
 * @extends BaseRepository<FloorPlan>
 */
class FloorPlanRepository extends BaseRepository
{
    protected string $model = FloorPlan::class;
}
