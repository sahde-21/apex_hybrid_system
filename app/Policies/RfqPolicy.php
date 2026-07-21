<?php

namespace App\Policies;

use App\Models\Rfq;
use App\Models\User;

class RfqPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('rfqs.read');
    }

    public function view(User $user, Rfq $rfq): bool
    {
        return $user->can('rfqs.read');
    }

    public function create(User $user): bool
    {
        return $user->can('rfqs.create');
    }

    public function update(User $user, Rfq $rfq): bool
    {
        return $user->can('rfqs.update') && $rfq->status->isEditable();
    }

    public function delete(User $user, Rfq $rfq): bool
    {
        return $user->can('rfqs.delete') && $rfq->status->isEditable();
    }

    public function send(User $user, Rfq $rfq): bool
    {
        return $user->can('rfqs.send') || $user->can('rfqs.update');
    }

    public function accept(User $user, Rfq $rfq): bool
    {
        return $user->can('rfqs.accept') || $user->can('rfqs.approve');
    }

    public function convert(User $user, Rfq $rfq): bool
    {
        return $user->can('rfqs.accept') || $user->can('rfqs.approve');
    }

    public function cancel(User $user, Rfq $rfq): bool
    {
        return $user->can('rfqs.update');
    }
}
