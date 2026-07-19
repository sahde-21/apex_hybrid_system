<?php

namespace App\Services;

use App\Repositories\PurchaseOrderRepository;

class PurchaseOrderService extends BaseService
{
    public function __construct(PurchaseOrderRepository $repository)
    {
        parent::__construct($repository);
    }
}
