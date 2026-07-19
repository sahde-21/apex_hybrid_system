<?php

namespace App\Repositories;

use App\Models\Shift;

/**
 * @extends BaseRepository<Shift>
 */
class ShiftRepository extends BaseRepository
{
    protected string $model = Shift::class;
}
