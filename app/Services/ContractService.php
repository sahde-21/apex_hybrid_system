<?php

namespace App\Services;

use App\Repositories\ContractRepository;
use App\Models\Contract;

/**
 * @extends BaseService<Contract>
 */
class ContractService extends BaseService
{
    public function __construct(ContractRepository $repository)
    {
        parent::__construct($repository);
    }
}
