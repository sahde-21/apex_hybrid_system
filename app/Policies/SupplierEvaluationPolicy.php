<?php

namespace App\Policies;

use App\Models\SupplierEvaluation;
use App\Models\User;

class SupplierEvaluationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('supplier-evaluations.read');
    }

    public function view(User $user, SupplierEvaluation $supplierEvaluation): bool
    {
        return $user->can('supplier-evaluations.read');
    }

    public function create(User $user): bool
    {
        return $user->can('supplier-evaluations.create');
    }

    public function update(User $user, SupplierEvaluation $supplierEvaluation): bool
    {
        return $user->can('supplier-evaluations.update');
    }

    public function delete(User $user, SupplierEvaluation $supplierEvaluation): bool
    {
        return $user->can('supplier-evaluations.delete');
    }
}
