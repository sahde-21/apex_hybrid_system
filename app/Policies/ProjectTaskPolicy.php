<?php

namespace App\Policies;

use App\Models\ProjectTask;
use App\Models\User;

class ProjectTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('project-tasks.read');
    }

    public function view(User $user, ProjectTask $projectTask): bool
    {
        return $user->can('project-tasks.read');
    }

    public function create(User $user): bool
    {
        return $user->can('project-tasks.create');
    }

    public function update(User $user, ProjectTask $projectTask): bool
    {
        return $user->can('project-tasks.update');
    }

    public function delete(User $user, ProjectTask $projectTask): bool
    {
        return $user->can('project-tasks.delete');
    }
}
