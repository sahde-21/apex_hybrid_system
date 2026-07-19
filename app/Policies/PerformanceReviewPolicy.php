<?php

namespace App\Policies;

use App\Models\PerformanceReview;
use App\Models\User;

class PerformanceReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('performance-reviews.read');
    }

    public function view(User $user, PerformanceReview $performanceReview): bool
    {
        return $user->can('performance-reviews.read');
    }

    public function create(User $user): bool
    {
        return $user->can('performance-reviews.create');
    }

    public function update(User $user, PerformanceReview $performanceReview): bool
    {
        return $user->can('performance-reviews.update');
    }

    public function delete(User $user, PerformanceReview $performanceReview): bool
    {
        return $user->can('performance-reviews.delete');
    }
}
