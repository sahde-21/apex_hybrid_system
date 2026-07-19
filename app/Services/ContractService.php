<?php

namespace App\Services;

use App\Repositories\ContractRepository;

class ContractService extends BaseService
{
    public function __construct(ContractRepository $repository)
    {
        parent::__construct($repository);
    }
}
