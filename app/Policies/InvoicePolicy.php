<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.read');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.read');
    }

    public function create(User $user): bool
    {
        return $user->can('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.update');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.delete');
    }

    public function print(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.print');
    }

    public function export(User $user): bool
    {
        return $user->can('invoices.export');
    }

    public function approve(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.approve');
    }
}
