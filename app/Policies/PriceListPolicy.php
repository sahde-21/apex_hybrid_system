<?php

namespace App\Policies;

use App\Models\PriceList;
use App\Models\User;

class PriceListPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('price-lists.read');
    }

    public function view(User $user, PriceList $priceList): bool
    {
        return $user->can('price-lists.read');
    }

    public function create(User $user): bool
    {
        return $user->can('price-lists.create');
    }

    public function update(User $user, PriceList $priceList): bool
    {
        return $user->can('price-lists.update');
    }

    public function delete(User $user, PriceList $priceList): bool
    {
        return $user->can('price-lists.delete');
    }
}
