<?php

namespace App\Repositories;

use App\Models\QualityControl;

/**
 * @extends BaseRepository<QualityControl>
 */
class QualityControlRepository extends BaseRepository
{
    protected string $model = QualityControl::class;
}
