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
        return $user->can('bills.update') && $bill->status->isEditable();
    }

    public function delete(User $user, Bill $bill): bool
    {
        return $user->can('bills.delete') && $bill->status->isEditable();
    }

    public function issue(User $user, Bill $bill): bool
    {
        return $user->can('bills.issue') || $user->can('bills.approve');
    }

    public function void(User $user, Bill $bill): bool
    {
        return $user->can('bills.void') || $user->can('bills.approve');
    }

    public function cancel(User $user, Bill $bill): bool
    {
        return $user->can('bills.update');
    }

    public function print(User $user, Bill $bill): bool
    {
        return $user->can('bills.print');
    }

    public function approve(User $user, Bill $bill): bool
    {
        return $user->can('bills.approve');
    }
}
