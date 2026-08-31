<?php

namespace App\Services;

use App\Repositories\BudgetRepository;
use App\Models\Budget;

/**
 * @extends BaseService<Budget>
 */
class BudgetService extends BaseService
{
    public function __construct(BudgetRepository $repository)
    {
        parent::__construct($repository);
    }
}
