<?php

namespace App\Services;

use App\Repositories\TaxRateRepository;

class TaxRateService extends BaseService
{
    public function __construct(TaxRateRepository $repository)
    {
        parent::__construct($repository);
    }
}
