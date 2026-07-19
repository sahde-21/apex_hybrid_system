<?php

namespace App\Policies;

use App\Models\ManagedDocument;
use App\Models\User;
use App\Services\Documents\DocumentAccessService;

class ManagedDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documents.read');
    }

    public function view(User $user, ManagedDocument $document): bool
    {
        return $user->can('documents.read') && app(DocumentAccessService::class)->canAccess($user, $document);
    }

    public function create(User $user): bool
    {
        return $user->can('documents.create');
    }

    public function update(User $user, ManagedDocument $document): bool
    {
        return $user->can('documents.update') && app(DocumentAccessService::class)->canAccess($user, $document);
    }

    public function delete(User $user, ManagedDocument $document): bool
    {
        return $user->can('documents.delete') && app(DocumentAccessService::class)->canAccess($user, $document);
    }

    public function restore(User $user, ManagedDocument $document): bool
    {
        return $user->can('documents.update') && app(DocumentAccessService::class)->canAccess($user, $document);
    }

    public function forceDelete(User $user, ManagedDocument $document): bool
    {
        return $user->can('documents.delete') && $user->hasAnyRole(['super-admin', 'owner']);
    }

    public function download(User $user, ManagedDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function share(User $user, ManagedDocument $document): bool
    {
        return $user->can('documents.export') && $this->view($user, $document);
    }

    public function print(User $user, ManagedDocument $document): bool
    {
        return $user->can('documents.print') && $this->view($user, $document);
    }
}
