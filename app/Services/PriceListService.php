<?php

namespace App\Services;

use App\Repositories\PriceListRepository;
use App\Models\PriceList;

/**
 * @extends BaseService<PriceList>
 */
class PriceListService extends BaseService
{
    public function __construct(PriceListRepository $repository)
    {
        parent::__construct($repository);
    }
}
