<?php

namespace App\Policies;

use App\Models\DocumentFolder;
use App\Models\User;

class DocumentFolderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documents.read');
    }

    public function view(User $user, DocumentFolder $folder): bool
    {
        return $user->can('documents.read') && $this->passesScope($user, $folder);
    }

    public function create(User $user): bool
    {
        return $user->can('documents.create');
    }

    public function update(User $user, DocumentFolder $folder): bool
    {
        return $user->can('documents.update') && $this->passesScope($user, $folder);
    }

    public function delete(User $user, DocumentFolder $folder): bool
    {
        return $user->can('documents.delete') && $this->passesScope($user, $folder);
    }

    protected function passesScope(User $user, DocumentFolder $folder): bool
    {
        if ($user->hasAnyRole(['super-admin', 'owner', 'manager'])) {
            return true;
        }

        if ($folder->branch_id && ! $user->can('branches.read')) {
            return false;
        }

        if ($folder->contact_id && ! $user->can('contacts.read')) {
            return false;
        }

        return true;
    }
}
