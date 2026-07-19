<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;

class AttendanceService extends BaseService
{
    public function __construct(AttendanceRepository $repository)
    {
        parent::__construct($repository);
    }
}
