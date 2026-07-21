<?php

namespace App\Policies;

use App\Models\IntelligenceRecommendation;
use App\Models\User;

class IntelligenceRecommendationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('intelligence.recommendations.view');
    }

    public function view(User $user, IntelligenceRecommendation $recommendation): bool
    {
        return $user->can('intelligence.recommendations.view');
    }

    public function acknowledge(User $user, IntelligenceRecommendation $recommendation): bool
    {
        return $user->can('intelligence.recommendations.manage');
    }

    public function dismiss(User $user, IntelligenceRecommendation $recommendation): bool
    {
        return $user->can('intelligence.recommendations.manage');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, IntelligenceRecommendation $recommendation): bool
    {
        return false;
    }

    public function delete(User $user, IntelligenceRecommendation $recommendation): bool
    {
        return false;
    }
}
