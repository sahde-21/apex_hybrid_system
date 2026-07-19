<?php

namespace App\Policies;

use App\Models\DocumentShare;
use App\Models\User;

class DocumentSharePolicy
{
    public function create(User $user): bool
    {
        return $user->can('documents.export');
    }

    public function delete(User $user, DocumentShare $share): bool
    {
        return $user->can('documents.export')
            && ($share->created_by === $user->id || $user->hasAnyRole(['super-admin', 'owner', 'manager']));
    }
}
