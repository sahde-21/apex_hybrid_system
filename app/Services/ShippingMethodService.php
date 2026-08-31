<?php

namespace App\Services;

use App\Repositories\ShippingMethodRepository;
use App\Models\ShippingMethod;

/**
 * @extends BaseService<ShippingMethod>
 */
class ShippingMethodService extends BaseService
{
    public function __construct(ShippingMethodRepository $repository)
    {
        parent::__construct($repository);
    }
}
