<?php

namespace App\Policies;

use App\Models\CustomerFeedback;
use App\Models\User;

class CustomerFeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customer-feedback.read');
    }

    public function view(User $user, CustomerFeedback $customerFeedback): bool
    {
        return $user->can('customer-feedback.read');
    }

    public function create(User $user): bool
    {
        return $user->can('customer-feedback.create');
    }

    public function update(User $user, CustomerFeedback $customerFeedback): bool
    {
        return $user->can('customer-feedback.update');
    }

    public function delete(User $user, CustomerFeedback $customerFeedback): bool
    {
        return $user->can('customer-feedback.delete');
    }
}
