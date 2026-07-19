<?php

namespace App\Services;

use App\Repositories\PerformanceReviewRepository;

class PerformanceReviewService extends BaseService
{
    public function __construct(PerformanceReviewRepository $repository)
    {
        parent::__construct($repository);
    }
}
