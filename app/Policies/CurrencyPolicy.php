<?php

namespace App\Policies;

use App\Models\Currency;
use App\Models\User;

class CurrencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('currencies.read');
    }

    public function view(User $user, Currency $currency): bool
    {
        return $user->can('currencies.read');
    }

    public function create(User $user): bool
    {
        return $user->can('currencies.create');
    }

    public function update(User $user, Currency $currency): bool
    {
        return $user->can('currencies.update');
    }

    public function delete(User $user, Currency $currency): bool
    {
        return $user->can('currencies.delete') && ! $currency->is_base;
    }
}
