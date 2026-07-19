<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('coupons.read');
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.read');
    }

    public function create(User $user): bool
    {
        return $user->can('coupons.create');
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.update');
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.delete');
    }
}
