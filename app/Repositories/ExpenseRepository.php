<?php

namespace App\Repositories;

use App\Models\Expense;

/**
 * @extends BaseRepository<Expense>
 */
class ExpenseRepository extends BaseRepository
{
    protected string $model = Expense::class;
}
