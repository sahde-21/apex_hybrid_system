<?php

namespace App\Services;

use App\Repositories\LeaveRequestRepository;

class LeaveRequestService extends BaseService
{
    public function __construct(LeaveRequestRepository $repository)
    {
        parent::__construct($repository);
    }
}
