<?php

namespace App\Services;

use App\Repositories\StockTransferRepository;

class StockTransferService extends BaseService
{
    public function __construct(StockTransferRepository $repository)
    {
        parent::__construct($repository);
    }
}
