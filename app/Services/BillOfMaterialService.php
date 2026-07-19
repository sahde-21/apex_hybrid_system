<?php

namespace App\Services;

use App\Repositories\BillOfMaterialRepository;

class BillOfMaterialService extends BaseService
{
    public function __construct(BillOfMaterialRepository $repository)
    {
        parent::__construct($repository);
    }
}
