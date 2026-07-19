<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.read');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->can('attendance.read');
    }

    public function create(User $user): bool
    {
        return $user->can('attendance.create');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->can('attendance.update');
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->can('attendance.delete');
    }
}
