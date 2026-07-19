<?php

namespace App\Services;

use App\Repositories\EmployeeRepository;

class EmployeeService extends BaseService
{
    public function __construct(EmployeeRepository $repository)
    {
        parent::__construct($repository);
    }
}
