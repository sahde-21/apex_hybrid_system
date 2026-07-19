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
        return $user->can('payments.create') || $user->can('payments.record');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->can('payments.update') && $payment->status->isEditable();
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->can('payments.delete') && $payment->status->isEditable();
    }

    public function post(User $user, Payment $payment): bool
    {
        return $user->can('payments.post') || $user->can('payments.update');
    }

    public function reverse(User $user, Payment $payment): bool
    {
        return $user->can('payments.reverse') || $user->can('payments.approve');
    }

    public function cancel(User $user, Payment $payment): bool
    {
        return $user->can('payments.update');
    }

    public function print(User $user, Payment $payment): bool
    {
        return $user->can('payments.print');
    }
}
