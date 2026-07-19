<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('expenses.read');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->can('expenses.read');
    }

    public function create(User $user): bool
    {
        return $user->can('expenses.create');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->can('expenses.update');
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->can('expenses.delete');
    }
}
