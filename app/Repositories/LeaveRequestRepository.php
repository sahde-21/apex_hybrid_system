<?php

namespace App\Repositories;

use App\Models\LeaveRequest;

/**
 * @extends BaseRepository<LeaveRequest>
 */
class LeaveRequestRepository extends BaseRepository
{
    protected string $model = LeaveRequest::class;
}
