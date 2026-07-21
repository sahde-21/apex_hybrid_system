<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase-orders.read');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase-orders.read');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase-orders.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase-orders.update') && $purchaseOrder->status->isEditable();
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase-orders.delete') && $purchaseOrder->status->isEditable();
    }

    public function submit(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase-orders.submit') || $user->can('purchase-orders.update');
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase-orders.approve');
    }

    public function confirm(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase-orders.confirm') || $user->can('purchase-orders.approve');
    }

    public function bill(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase-orders.bill') || $user->can('bills.create');
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase-orders.update');
    }

    public function print(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase-orders.print');
    }
}
