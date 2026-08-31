<?php

namespace App\Services;

use App\Repositories\ProductionOrderRepository;
use App\Models\ProductionOrder;

/**
 * @extends BaseService<ProductionOrder>
 */
class ProductionOrderService extends BaseService
{
    public function __construct(ProductionOrderRepository $repository)
    {
        parent::__construct($repository);
    }
}
