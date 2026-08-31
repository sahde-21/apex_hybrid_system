<?php

namespace App\Services;

use App\Repositories\SaleOrderRepository;
use App\Models\SaleOrder;

/**
 * @extends BaseService<SaleOrder>
 */
class SaleOrderService extends BaseService
{
    public function __construct(SaleOrderRepository $repository)
    {
        parent::__construct($repository);
    }
}
