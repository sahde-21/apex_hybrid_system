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
        return $user->can('leave-requests.update') && $leaveRequest->status->isEditable();
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('leave-requests.delete') && $leaveRequest->status->isEditable();
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('leave-requests.approve') || $user->can('workflow.approve');
    }

    public function submit(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('workflow.submit') || $user->can('leave-requests.update');
    }
}
