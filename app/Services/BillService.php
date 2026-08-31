<?php

namespace App\Services;

use App\Repositories\BillRepository;
use App\Models\Bill;

/**
 * @extends BaseService<Bill>
 */
class BillService extends BaseService
{
    public function __construct(BillRepository $repository)
    {
        parent::__construct($repository);
    }
}
