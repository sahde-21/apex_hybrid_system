<?php

namespace App\Services;

use App\Repositories\TimeLogRepository;

class TimeLogService extends BaseService
{
    public function __construct(TimeLogRepository $repository)
    {
        parent::__construct($repository);
    }
}
