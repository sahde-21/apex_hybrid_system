<?php

namespace App\Services;

use App\Repositories\PerformanceReviewRepository;
use App\Models\PerformanceReview;

/**
 * @extends BaseService<PerformanceReview>
 */
class PerformanceReviewService extends BaseService
{
    public function __construct(PerformanceReviewRepository $repository)
    {
        parent::__construct($repository);
    }
}
