<?php

namespace App\Services;

use App\Repositories\WarehouseRepository;
use App\Models\Warehouse;

/**
 * @extends BaseService<Warehouse>
 */
class WarehouseService extends BaseService
{
    public function __construct(WarehouseRepository $repository)
    {
        parent::__construct($repository);
    }
}
