<?php

namespace App\Services;

use App\Repositories\ShiftRepository;
use App\Models\Shift;

/**
 * @extends BaseService<Shift>
 */
class ShiftService extends BaseService
{
    public function __construct(ShiftRepository $repository)
    {
        parent::__construct($repository);
    }
}
