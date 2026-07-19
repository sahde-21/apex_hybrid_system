<?php

namespace App\Repositories;

use App\Models\PriceList;

/**
 * @extends BaseRepository<PriceList>
 */
class PriceListRepository extends BaseRepository
{
    protected string $model = PriceList::class;
}
