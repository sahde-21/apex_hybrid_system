<?php

namespace App\Repositories;

use App\Models\Coupon;

/**
 * @extends BaseRepository<Coupon>
 */
class CouponRepository extends BaseRepository
{
    protected string $model = Coupon::class;
}
