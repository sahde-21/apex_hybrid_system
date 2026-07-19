<?php

namespace App\Policies;

use App\Models\TimeLog;
use App\Models\User;

class TimeLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('time-logs.read');
    }

    public function view(User $user, TimeLog $timeLog): bool
    {
        return $user->can('time-logs.read');
    }

    public function create(User $user): bool
    {
        return $user->can('time-logs.create');
    }

    public function update(User $user, TimeLog $timeLog): bool
    {
        return $user->can('time-logs.update');
    }

    public function delete(User $user, TimeLog $timeLog): bool
    {
        return $user->can('time-logs.delete');
    }
}
