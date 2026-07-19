<?php

namespace App\Repositories;

use App\Models\DeliveryTrip;

/**
 * @extends BaseRepository<DeliveryTrip>
 */
class DeliveryTripRepository extends BaseRepository
{
    protected string $model = DeliveryTrip::class;
}
