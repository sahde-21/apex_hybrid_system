<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('chart-of-accounts.read');
    }

    public function view(User $user, Account $account): bool
    {
        return $user->can('chart-of-accounts.read');
    }

    public function create(User $user): bool
    {
        return $user->can('chart-of-accounts.create');
    }

    public function update(User $user, Account $account): bool
    {
        return $user->can('chart-of-accounts.update');
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->can('chart-of-accounts.delete') && ! $account->is_system;
    }
}
