<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quotations.read');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.read');
    }

    public function create(User $user): bool
    {
        return $user->can('quotations.create');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.update') && $quotation->status->isEditable();
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.delete') && $quotation->status->isEditable();
    }

    public function send(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.send') || $user->can('quotations.update');
    }

    public function approve(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.approve');
    }

    public function reject(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.reject') || $user->can('quotations.approve');
    }

    public function convert(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.convert') || $user->can('quotations.approve');
    }

    public function cancel(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.update');
    }

    public function print(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.print');
    }
}
