<?php

namespace App\Services;

use App\Repositories\DeliveryTripRepository;

class DeliveryTripService extends BaseService
{
    public function __construct(DeliveryTripRepository $repository)
    {
        parent::__construct($repository);
    }
}
