<?php

namespace App\Repositories;

use App\Models\Budget;

/**
 * @extends BaseRepository<Budget>
 */
class BudgetRepository extends BaseRepository
{
    protected string $model = Budget::class;
}
