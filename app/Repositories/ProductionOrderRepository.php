<?php

namespace App\Repositories;

use App\Models\ProductionOrder;

/**
 * @extends BaseRepository<ProductionOrder>
 */
class ProductionOrderRepository extends BaseRepository
{
    protected string $model = ProductionOrder::class;
}
