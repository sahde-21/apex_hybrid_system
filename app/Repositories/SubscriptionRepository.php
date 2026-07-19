<?php

namespace App\Repositories;

use App\Models\Subscription;

/**
 * @extends BaseRepository<Subscription>
 */
class SubscriptionRepository extends BaseRepository
{
    protected string $model = Subscription::class;
}
