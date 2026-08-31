<?php

namespace App\Services;

use App\Repositories\CouponRepository;
use App\Models\Coupon;

/**
 * @extends BaseService<Coupon>
 */
class CouponService extends BaseService
{
    public function __construct(CouponRepository $repository)
    {
        parent::__construct($repository);
    }
}
