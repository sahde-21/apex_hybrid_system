<?php

namespace App\Policies;

use App\Models\FiscalPeriod;
use App\Models\User;

class FiscalPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('fiscal-periods.read');
    }

    public function view(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->can('fiscal-periods.read');
    }

    public function create(User $user): bool
    {
        return $user->can('fiscal-periods.create') || $user->can('fiscal-periods.manage');
    }

    public function update(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->can('fiscal-periods.update') || $user->can('fiscal-periods.manage');
    }

    public function delete(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->can('fiscal-periods.delete') || $user->can('fiscal-periods.manage');
    }

    public function manage(User $user, ?FiscalPeriod $fiscalPeriod = null): bool
    {
        return $user->can('fiscal-periods.manage');
    }
}
