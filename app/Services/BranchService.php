<?php

namespace App\Services;

use App\Repositories\BranchRepository;

class BranchService extends BaseService
{
    public function __construct(BranchRepository $repository)
    {
        parent::__construct($repository);
    }
}
