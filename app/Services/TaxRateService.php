<?php

namespace App\Services;

use App\Repositories\TaxRateRepository;
use App\Models\TaxRate;

/**
 * @extends BaseService<TaxRate>
 */
class TaxRateService extends BaseService
{
    public function __construct(TaxRateRepository $repository)
    {
        parent::__construct($repository);
    }
}
