<?php

namespace App\Policies;

use App\Models\QualityControl;
use App\Models\User;

class QualityControlPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quality-control.read');
    }

    public function view(User $user, QualityControl $qualityControl): bool
    {
        return $user->can('quality-control.read');
    }

    public function create(User $user): bool
    {
        return $user->can('quality-control.create');
    }

    public function update(User $user, QualityControl $qualityControl): bool
    {
        return $user->can('quality-control.update');
    }

    public function delete(User $user, QualityControl $qualityControl): bool
    {
        return $user->can('quality-control.delete');
    }
}
