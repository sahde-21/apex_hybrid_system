<?php

namespace App\Services;

use App\Repositories\LoyaltyProgramRepository;

class LoyaltyProgramService extends BaseService
{
    public function __construct(LoyaltyProgramRepository $repository)
    {
        parent::__construct($repository);
    }
}
