<?php

namespace App\Services;

use App\Repositories\BankReconciliationRepository;

class BankReconciliationService extends BaseService
{
    public function __construct(BankReconciliationRepository $repository)
    {
        parent::__construct($repository);
    }
}
