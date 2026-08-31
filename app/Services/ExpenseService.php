<?php

namespace App\Services;

use App\Repositories\ExpenseRepository;
use App\Models\Expense;

/**
 * @extends BaseService<Expense>
 */
class ExpenseService extends BaseService
{
    public function __construct(ExpenseRepository $repository)
    {
        parent::__construct($repository);
    }
}
