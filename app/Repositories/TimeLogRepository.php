<?php

namespace App\Repositories;

use App\Models\TimeLog;

/**
 * @extends BaseRepository<TimeLog>
 */
class TimeLogRepository extends BaseRepository
{
    protected string $model = TimeLog::class;
}
