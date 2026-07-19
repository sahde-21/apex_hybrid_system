<?php

namespace App\Services;

use App\Repositories\SaleOrderRepository;

class SaleOrderService extends BaseService
{
    public function __construct(SaleOrderRepository $repository)
    {
        parent::__construct($repository);
    }
}
