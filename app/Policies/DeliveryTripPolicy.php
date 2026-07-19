<?php

namespace App\Policies;

use App\Models\DeliveryTrip;
use App\Models\User;

class DeliveryTripPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('delivery-trips.read');
    }

    public function view(User $user, DeliveryTrip $deliveryTrip): bool
    {
        return $user->can('delivery-trips.read');
    }

    public function create(User $user): bool
    {
        return $user->can('delivery-trips.create');
    }

    public function update(User $user, DeliveryTrip $deliveryTrip): bool
    {
        return $user->can('delivery-trips.update');
    }

    public function delete(User $user, DeliveryTrip $deliveryTrip): bool
    {
        return $user->can('delivery-trips.delete');
    }
}
