<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payrolls.read');
    }

    public function view(User $user, Payroll $payroll): bool
    {
        return $user->can('payrolls.read');
    }

    public function create(User $user): bool
    {
        return $user->can('payrolls.create');
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return $user->can('payrolls.update');
    }

    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->can('payrolls.delete');
    }
}
