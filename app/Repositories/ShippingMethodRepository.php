<?php

namespace App\Repositories;

use App\Models\ShippingMethod;

/**
 * @extends BaseRepository<ShippingMethod>
 */
class ShippingMethodRepository extends BaseRepository
{
    protected string $model = ShippingMethod::class;
}
