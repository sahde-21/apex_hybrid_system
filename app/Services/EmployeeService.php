<?php

namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Models\Employee;

/**
 * @extends BaseService<Employee>
 */
class EmployeeService extends BaseService
{
    public function __construct(EmployeeRepository $repository)
    {
        parent::__construct($repository);
    }
}
