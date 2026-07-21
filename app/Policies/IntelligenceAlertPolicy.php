<?php

namespace App\Policies;

use App\Models\IntelligenceAlert;
use App\Models\User;

class IntelligenceAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('intelligence.alerts.view');
    }

    public function view(User $user, IntelligenceAlert $alert): bool
    {
        return $user->can('intelligence.alerts.view');
    }

    public function acknowledge(User $user, IntelligenceAlert $alert): bool
    {
        return $user->can('intelligence.alerts.manage');
    }

    public function dismiss(User $user, IntelligenceAlert $alert): bool
    {
        return $user->can('intelligence.alerts.manage');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, IntelligenceAlert $alert): bool
    {
        return false;
    }

    public function delete(User $user, IntelligenceAlert $alert): bool
    {
        return false;
    }
}
