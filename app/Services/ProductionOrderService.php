<?php

namespace App\Services;

use App\Repositories\ProductionOrderRepository;

class ProductionOrderService extends BaseService
{
    public function __construct(ProductionOrderRepository $repository)
    {
        parent::__construct($repository);
    }
}
