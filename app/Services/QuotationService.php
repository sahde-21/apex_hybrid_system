<?php

namespace App\Services;

use App\Repositories\QuotationRepository;

class QuotationService extends BaseService
{
    public function __construct(QuotationRepository $repository)
    {
        parent::__construct($repository);
    }
}
