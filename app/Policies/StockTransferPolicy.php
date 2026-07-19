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
        return $user->can('stock-transfers.update');
    }

    public function delete(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock-transfers.delete');
    }
}
