<?php

namespace App\Services;

use App\Repositories\CouponRepository;

class CouponService extends BaseService
{
    public function __construct(CouponRepository $repository)
    {
        parent::__construct($repository);
    }
}
