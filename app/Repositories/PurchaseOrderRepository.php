<?php

namespace App\Repositories;

use App\Models\PurchaseOrder;

/**
 * @extends BaseRepository<PurchaseOrder>
 */
class PurchaseOrderRepository extends BaseRepository
{
    protected string $model = PurchaseOrder::class;
}
