<?php

namespace App\Services;

use App\Repositories\InventoryAdjustmentRepository;
use App\Models\InventoryAdjustment;

/**
 * @extends BaseService<InventoryAdjustment>
 */
class InventoryAdjustmentService extends BaseService
{
    public function __construct(InventoryAdjustmentRepository $repository)
    {
        parent::__construct($repository);
    }
}
