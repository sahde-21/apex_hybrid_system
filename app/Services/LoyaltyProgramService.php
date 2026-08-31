<?php

namespace App\Services;

use App\Repositories\LoyaltyProgramRepository;
use App\Models\LoyaltyProgram;

/**
 * @extends BaseService<LoyaltyProgram>
 */
class LoyaltyProgramService extends BaseService
{
    public function __construct(LoyaltyProgramRepository $repository)
    {
        parent::__construct($repository);
    }
}
