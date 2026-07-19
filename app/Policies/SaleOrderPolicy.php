<?php

namespace App\Policies;

use App\Models\SaleOrder;
use App\Models\User;

class SaleOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sale-orders.read');
    }

    public function view(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.read');
    }

    public function create(User $user): bool
    {
        return $user->can('sale-orders.create');
    }

    public function update(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.update') && $saleOrder->status->isEditable();
    }

    public function delete(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.delete') && $saleOrder->status->isEditable();
    }

    public function submit(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.submit') || $user->can('sale-orders.update');
    }

    public function approve(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.approve');
    }

    public function confirm(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.confirm') || $user->can('sale-orders.approve');
    }

    public function invoice(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.invoice') || $user->can('invoices.create');
    }

    public function cancel(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.update');
    }

    public function print(User $user, SaleOrder $saleOrder): bool
    {
        return $user->can('sale-orders.print');
    }
}
