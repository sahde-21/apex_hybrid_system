<?php

namespace App\Services;

use App\Repositories\ExpenseRepository;

class ExpenseService extends BaseService
{
    public function __construct(ExpenseRepository $repository)
    {
        parent::__construct($repository);
    }
}
