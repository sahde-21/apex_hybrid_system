<?php

namespace App\Repositories;

use App\Models\Attendance;

/**
 * @extends BaseRepository<Attendance>
 */
class AttendanceRepository extends BaseRepository
{
    protected string $model = Attendance::class;
}
