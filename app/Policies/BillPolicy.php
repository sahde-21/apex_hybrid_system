<?php

namespace App\Policies;

use App\Models\Bill;
use App\Models\User;

class BillPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bills.read');
    }

    public function view(User $user, Bill $bill): bool
    {
        return $user->can('bills.read');
    }

    public function create(User $user): bool
    {
        return $user->can('bills.create');
    }

    public function update(User $user, Bill $bill): bool
    {
        return $user->can('bills.update');
    }

    public function delete(User $user, Bill $bill): bool
    {
        return $user->can('bills.delete');
    }
}
