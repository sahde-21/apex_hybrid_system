<?php

namespace App\Services;

use App\Repositories\WarehouseRepository;

class WarehouseService extends BaseService
{
    public function __construct(WarehouseRepository $repository)
    {
        parent::__construct($repository);
    }
}
