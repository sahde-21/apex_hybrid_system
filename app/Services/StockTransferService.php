<?php

namespace App\Services;

use App\Repositories\StockTransferRepository;
use App\Models\StockTransfer;

/**
 * @extends BaseService<StockTransfer>
 */
class StockTransferService extends BaseService
{
    public function __construct(StockTransferRepository $repository)
    {
        parent::__construct($repository);
    }
}
