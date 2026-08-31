<?php

namespace App\Services;

use App\Repositories\BankReconciliationRepository;
use App\Models\BankReconciliation;

/**
 * @extends BaseService<BankReconciliation>
 */
class BankReconciliationService extends BaseService
{
    public function __construct(BankReconciliationRepository $repository)
    {
        parent::__construct($repository);
    }
}
