<?php

namespace App\Services;

use App\Repositories\TimeLogRepository;
use App\Models\TimeLog;

/**
 * @extends BaseService<TimeLog>
 */
class TimeLogService extends BaseService
{
    public function __construct(TimeLogRepository $repository)
    {
        parent::__construct($repository);
    }
}
