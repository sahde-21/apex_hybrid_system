<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock-transfers.read');
    }

    public function view(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock-transfers.read');
    }

    public function create(User $user): bool
    {
        return $user->can('stock-transfers.create');
    }

    public function update(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock-transfers.update')
            && $stockTransfer->status->isEditable();
    }

    public function delete(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock-transfers.delete')
            && $stockTransfer->status->isEditable();
    }

    public function approve(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock-transfers.approve')
            && $stockTransfer->status->canApprove();
    }

    public function ship(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock-transfers.approve')
            && $stockTransfer->status->canShip();
    }

    public function receive(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock-transfers.approve')
            && $stockTransfer->status->canReceive();
    }

    public function cancel(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock-transfers.update')
            && $stockTransfer->status->canCancel();
    }
}
