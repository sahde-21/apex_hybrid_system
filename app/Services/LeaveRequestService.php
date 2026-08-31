<?php

namespace App\Services;

use App\Repositories\LeaveRequestRepository;
use App\Models\LeaveRequest;

/**
 * @extends BaseService<LeaveRequest>
 */
class LeaveRequestService extends BaseService
{
    public function __construct(LeaveRequestRepository $repository)
    {
        parent::__construct($repository);
    }
}
