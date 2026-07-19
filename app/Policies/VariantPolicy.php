<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Variant;

class VariantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('variants.read');
    }

    public function view(User $user, Variant $variant): bool
    {
        return $user->can('variants.read');
    }

    public function create(User $user): bool
    {
        return $user->can('variants.create');
    }

    public function update(User $user, Variant $variant): bool
    {
        return $user->can('variants.update');
    }

    public function delete(User $user, Variant $variant): bool
    {
        return $user->can('variants.delete');
    }
}
