<?php

namespace App\Services;

use App\Repositories\ShiftRepository;

class ShiftService extends BaseService
{
    public function __construct(ShiftRepository $repository)
    {
        parent::__construct($repository);
    }
}
