<?php

namespace App\Services;

use App\Repositories\ShippingMethodRepository;

class ShippingMethodService extends BaseService
{
    public function __construct(ShippingMethodRepository $repository)
    {
        parent::__construct($repository);
    }
}
