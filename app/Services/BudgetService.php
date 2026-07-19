<?php

namespace App\Services;

use App\Repositories\BudgetRepository;

class BudgetService extends BaseService
{
    public function __construct(BudgetRepository $repository)
    {
        parent::__construct($repository);
    }
}
