<?php

namespace App\Repositories;

use App\Models\InventoryAdjustment;

/**
 * @extends BaseRepository<InventoryAdjustment>
 */
class InventoryAdjustmentRepository extends BaseRepository
{
    protected string $model = InventoryAdjustment::class;
}
