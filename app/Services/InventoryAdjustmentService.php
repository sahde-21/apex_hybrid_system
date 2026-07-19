<?php

namespace App\Services;

use App\Repositories\InventoryAdjustmentRepository;

class InventoryAdjustmentService extends BaseService
{
    public function __construct(InventoryAdjustmentRepository $repository)
    {
        parent::__construct($repository);
    }
}
