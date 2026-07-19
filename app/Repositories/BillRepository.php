<?php

namespace App\Repositories;

use App\Models\Bill;

/**
 * @extends BaseRepository<Bill>
 */
class BillRepository extends BaseRepository
{
    protected string $model = Bill::class;
}
