<?php

namespace App\Repositories;

use App\Models\StockTransfer;

/**
 * @extends BaseRepository<StockTransfer>
 */
class StockTransferRepository extends BaseRepository
{
    protected string $model = StockTransfer::class;
}
