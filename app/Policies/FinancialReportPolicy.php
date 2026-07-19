<?php

namespace App\Policies;

use App\Models\FinancialReport;
use App\Models\User;

class FinancialReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('financial-reports.read');
    }

    public function view(User $user, FinancialReport $financialReport): bool
    {
        return $user->can('financial-reports.read');
    }

    public function create(User $user): bool
    {
        return $user->can('financial-reports.create');
    }

    public function update(User $user, FinancialReport $financialReport): bool
    {
        return $user->can('financial-reports.update');
    }

    public function delete(User $user, FinancialReport $financialReport): bool
    {
        return $user->can('financial-reports.delete');
    }
}
