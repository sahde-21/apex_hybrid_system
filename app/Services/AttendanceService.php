<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Models\Attendance;

/**
 * @extends BaseService<Attendance>
 */
class AttendanceService extends BaseService
{
    public function __construct(AttendanceRepository $repository)
    {
        parent::__construct($repository);
    }
}
