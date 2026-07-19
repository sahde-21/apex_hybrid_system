<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('budgeting.read');
    }

    public function view(User $user, Budget $budget): bool
    {
        return $user->can('budgeting.read');
    }

    public function create(User $user): bool
    {
        return $user->can('budgeting.create');
    }

    public function update(User $user, Budget $budget): bool
    {
        return $user->can('budgeting.update');
    }

    public function delete(User $user, Budget $budget): bool
    {
        return $user->can('budgeting.delete');
    }
}
