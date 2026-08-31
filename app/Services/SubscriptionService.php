<?php

namespace App\Services;

use App\Repositories\SubscriptionRepository;
use App\Models\Subscription;

/**
 * @extends BaseService<Subscription>
 */
class SubscriptionService extends BaseService
{
    public function __construct(SubscriptionRepository $repository)
    {
        parent::__construct($repository);
    }
}
