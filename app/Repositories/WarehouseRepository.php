<?php

namespace App\Repositories;

use App\Models\Warehouse;

/**
 * @extends BaseRepository<Warehouse>
 */
class WarehouseRepository extends BaseRepository
{
    protected string $model = Warehouse::class;
}
