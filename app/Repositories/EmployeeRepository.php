<?php

namespace App\Repositories;

use App\Models\Employee;

/**
 * @extends BaseRepository<Employee>
 */
class EmployeeRepository extends BaseRepository
{
    protected string $model = Employee::class;
}
