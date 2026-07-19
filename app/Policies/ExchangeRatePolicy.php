<?php

namespace App\Policies;

use App\Models\ExchangeRate;
use App\Models\User;

class ExchangeRatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('currencies.read');
    }

    public function view(User $user, ExchangeRate $exchangeRate): bool
    {
        return $user->can('currencies.read');
    }

    public function create(User $user): bool
    {
        return $user->can('currencies.create') || $user->can('currencies.update');
    }

    public function update(User $user, ExchangeRate $exchangeRate): bool
    {
        return $user->can('currencies.update');
    }

    public function delete(User $user, ExchangeRate $exchangeRate): bool
    {
        return $user->can('currencies.delete') || $user->can('currencies.update');
    }
}
