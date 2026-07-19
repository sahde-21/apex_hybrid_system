<?php

namespace App\Services;

use App\Repositories\PayrollRepository;

class PayrollService extends BaseService
{
    public function __construct(PayrollRepository $repository)
    {
        parent::__construct($repository);
    }
}
