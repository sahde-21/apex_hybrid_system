<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase-requests.read');
    }

    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase-requests.read');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase-requests.create');
    }

    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase-requests.update') && $purchaseRequest->status->isEditable();
    }

    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase-requests.delete') && $purchaseRequest->status->isEditable();
    }

    public function submit(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase-requests.submit') || $user->can('purchase-requests.update');
    }

    public function approve(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase-requests.approve');
    }

    public function convert(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase-requests.convert') || $user->can('purchase-requests.approve');
    }

    public function cancel(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase-requests.update');
    }
}
