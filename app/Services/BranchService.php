<?php

namespace App\Services;

use App\Repositories\BranchRepository;
use App\Models\Branch;

/**
 * @extends BaseService<Branch>
 */
class BranchService extends BaseService
{
    public function __construct(BranchRepository $repository)
    {
        parent::__construct($repository);
    }
}
