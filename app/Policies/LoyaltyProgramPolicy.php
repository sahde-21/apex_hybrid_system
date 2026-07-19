<?php

namespace App\Policies;

use App\Models\LoyaltyProgram;
use App\Models\User;

class LoyaltyProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('loyalty-programs.read');
    }

    public function view(User $user, LoyaltyProgram $loyaltyProgram): bool
    {
        return $user->can('loyalty-programs.read');
    }

    public function create(User $user): bool
    {
        return $user->can('loyalty-programs.create');
    }

    public function update(User $user, LoyaltyProgram $loyaltyProgram): bool
    {
        return $user->can('loyalty-programs.update');
    }

    public function delete(User $user, LoyaltyProgram $loyaltyProgram): bool
    {
        return $user->can('loyalty-programs.delete');
    }
}
