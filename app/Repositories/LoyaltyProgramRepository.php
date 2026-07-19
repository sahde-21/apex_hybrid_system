<?php

namespace App\Repositories;

use App\Models\LoyaltyProgram;

/**
 * @extends BaseRepository<LoyaltyProgram>
 */
class LoyaltyProgramRepository extends BaseRepository
{
    protected string $model = LoyaltyProgram::class;
}
