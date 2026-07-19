<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leave-requests.read');
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('leave-requests.read');
    }

    public function create(User $user): bool
    {
        return $user->can('leave-requests.create');
    }

    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('leave-requests.update');
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('leave-requests.delete');
    }
}
