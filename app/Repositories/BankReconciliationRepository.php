<?php

namespace App\Repositories;

use App\Models\BankReconciliation;

/**
 * @extends BaseRepository<BankReconciliation>
 */
class BankReconciliationRepository extends BaseRepository
{
    protected string $model = BankReconciliation::class;
}
