<?php

namespace App\Repositories;

use App\Models\Branch;

/**
 * @extends BaseRepository<Branch>
 */
class BranchRepository extends BaseRepository
{
    protected string $model = Branch::class;
}
