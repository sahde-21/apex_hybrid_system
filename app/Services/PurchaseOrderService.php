<?php

namespace App\Services;

use App\Repositories\PurchaseOrderRepository;
use App\Models\PurchaseOrder;

/**
 * @extends BaseService<PurchaseOrder>
 */
class PurchaseOrderService extends BaseService
{
    public function __construct(PurchaseOrderRepository $repository)
    {
        parent::__construct($repository);
    }
}
