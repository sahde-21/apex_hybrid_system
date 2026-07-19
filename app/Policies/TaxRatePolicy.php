<?php

namespace App\Policies;

use App\Models\TaxRate;
use App\Models\User;

class TaxRatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tax-rates.read');
    }

    public function view(User $user, TaxRate $taxRate): bool
    {
        return $user->can('tax-rates.read');
    }

    public function create(User $user): bool
    {
        return $user->can('tax-rates.create');
    }

    public function update(User $user, TaxRate $taxRate): bool
    {
        return $user->can('tax-rates.update');
    }

    public function delete(User $user, TaxRate $taxRate): bool
    {
        return $user->can('tax-rates.delete');
    }
}
