<?php

namespace App\Services;

use App\Repositories\SubscriptionRepository;

class SubscriptionService extends BaseService
{
    public function __construct(SubscriptionRepository $repository)
    {
        parent::__construct($repository);
    }
}
