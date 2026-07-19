<?php

namespace App\Policies;

use App\Models\FixedAsset;
use App\Models\User;

class FixedAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('fixed-assets.read');
    }

    public function view(User $user, FixedAsset $fixedAsset): bool
    {
        return $user->can('fixed-assets.read');
    }

    public function create(User $user): bool
    {
        return $user->can('fixed-assets.create');
    }

    public function update(User $user, FixedAsset $fixedAsset): bool
    {
        return $user->can('fixed-assets.update');
    }

    public function delete(User $user, FixedAsset $fixedAsset): bool
    {
        return $user->can('fixed-assets.delete');
    }
}
