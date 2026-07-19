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
        return $user->can('invoices.update') && $invoice->status->isEditable();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.delete') && $invoice->status->isEditable();
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.issue') || $user->can('invoices.approve');
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.void') || $user->can('invoices.approve');
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.update');
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
