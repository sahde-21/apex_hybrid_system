<?php

namespace App\Repositories;

use App\Models\SaleOrder;

/**
 * @extends BaseRepository<SaleOrder>
 */
class SaleOrderRepository extends BaseRepository
{
    protected string $model = SaleOrder::class;
}
