<?php

namespace App\Policies;

use App\Models\CrmInteraction;
use App\Models\User;

class CrmInteractionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm-interactions.read');
    }

    public function view(User $user, CrmInteraction $crmInteraction): bool
    {
        return $user->can('crm-interactions.read');
    }

    public function create(User $user): bool
    {
        return $user->can('crm-interactions.create');
    }

    public function update(User $user, CrmInteraction $crmInteraction): bool
    {
        return $user->can('crm-interactions.update');
    }

    public function delete(User $user, CrmInteraction $crmInteraction): bool
    {
        return $user->can('crm-interactions.delete');
    }
}
