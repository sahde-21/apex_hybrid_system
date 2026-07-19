<?php

namespace App\Repositories;

use App\Models\PerformanceReview;

/**
 * @extends BaseRepository<PerformanceReview>
 */
class PerformanceReviewRepository extends BaseRepository
{
    protected string $model = PerformanceReview::class;
}
