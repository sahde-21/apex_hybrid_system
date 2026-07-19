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
        return $user->can('quotations.update');
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.delete');
    }
}
