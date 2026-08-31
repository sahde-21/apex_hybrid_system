<?php

namespace App\Services;

use App\Repositories\PayrollRepository;
use App\Models\Payroll;

/**
 * @extends BaseService<Payroll>
 */
class PayrollService extends BaseService
{
    public function __construct(PayrollRepository $repository)
    {
        parent::__construct($repository);
    }
}
