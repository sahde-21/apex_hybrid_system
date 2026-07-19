<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('campaigns.read');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->can('campaigns.read');
    }

    public function create(User $user): bool
    {
        return $user->can('campaigns.create');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->can('campaigns.update');
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->can('campaigns.delete');
    }
}
