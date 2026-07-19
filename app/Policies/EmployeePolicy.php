<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employees.read');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('employees.read');
    }

    public function create(User $user): bool
    {
        return $user->can('employees.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.update');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('employees.delete');
    }
}
