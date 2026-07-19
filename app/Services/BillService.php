<?php

namespace App\Services;

use App\Repositories\BillRepository;

class BillService extends BaseService
{
    public function __construct(BillRepository $repository)
    {
        parent::__construct($repository);
    }
}
