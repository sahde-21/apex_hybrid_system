<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leads.read');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->can('leads.read');
    }

    public function create(User $user): bool
    {
        return $user->can('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('leads.update');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete');
    }
}
