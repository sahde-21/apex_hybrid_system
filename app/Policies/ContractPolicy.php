<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contracts.read');
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->can('contracts.read');
    }

    public function create(User $user): bool
    {
        return $user->can('contracts.create');
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->can('contracts.update');
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->can('contracts.delete');
    }
}
