<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.read');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('products.read');
    }

    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('products.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('products.export');
    }

    public function print(User $user, Product $product): bool
    {
        return $user->can('products.print');
    }
}
