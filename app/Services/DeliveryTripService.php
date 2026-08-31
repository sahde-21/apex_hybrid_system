<?php

namespace App\Services;

use App\Repositories\DeliveryTripRepository;
use App\Models\DeliveryTrip;

/**
 * @extends BaseService<DeliveryTrip>
 */
class DeliveryTripService extends BaseService
{
    public function __construct(DeliveryTripRepository $repository)
    {
        parent::__construct($repository);
    }
}
