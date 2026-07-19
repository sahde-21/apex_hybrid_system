<?php

namespace App\Policies;

use App\Models\BankReconciliation;
use App\Models\User;

class BankReconciliationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bank-reconciliation.read');
    }

    public function view(User $user, BankReconciliation $bankReconciliation): bool
    {
        return $user->can('bank-reconciliation.read');
    }

    public function create(User $user): bool
    {
        return $user->can('bank-reconciliation.create');
    }

    public function update(User $user, BankReconciliation $bankReconciliation): bool
    {
        return $user->can('bank-reconciliation.update');
    }

    public function delete(User $user, BankReconciliation $bankReconciliation): bool
    {
        return $user->can('bank-reconciliation.delete');
    }
}
