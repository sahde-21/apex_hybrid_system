<?php

namespace App\Repositories;

use App\Models\Contract;

/**
 * @extends BaseRepository<Contract>
 */
class ContractRepository extends BaseRepository
{
    protected string $model = Contract::class;
}
