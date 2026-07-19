<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.read');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payments.read');
    }

    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->can('payments.update');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->can('payments.delete');
    }
}
