<?php

namespace App\Services\Documents;

use App\Enums\DocumentCategory;
use App\Models\Employee;
use App\Models\ManagedDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DocumentAccessService
{
    public function canAccess(User $user, ManagedDocument $document): bool
    {
        if ($user->hasAnyRole(['super-admin', 'owner', 'manager'])) {
            return true;
        }

        if ($document->owner_id === $user->id || $document->created_by === $user->id) {
            return true;
        }

        if ($document->category === DocumentCategory::Company && ! $user->can('settings.read')) {
            return false;
        }

        if ($document->branch_id && ! $user->can('branches.read')) {
            return false;
        }

        if ($document->contact_id && ! $user->can('contacts.read')) {
            return false;
        }

        if ($document->department) {
            $department = $this->userDepartment($user);

            if ($department !== null && strcasecmp($department, $document->department) !== 0) {
                return false;
            }
        }

        if (in_array($document->category, [DocumentCategory::Customers, DocumentCategory::Suppliers], true)
            && $document->contact_id
            && ! $user->can('contacts.read')) {
            return false;
        }

        return true;
    }

    /**
     * @param  Builder<ManagedDocument>  $query
     */
    public function applyListScope(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['super-admin', 'owner', 'manager'])) {
            return;
        }

        $department = $this->userDepartment($user);

        $query->where(function (Builder $q) use ($user, $department) {
            $q->where('owner_id', $user->id)
                ->orWhere('created_by', $user->id);

            $q->orWhere(function (Builder $visible) use ($user, $department) {
                $visible->where(function (Builder $branchScope) use ($user) {
                    $branchScope->whereNull('branch_id');
                    if ($user->can('branches.read')) {
                        $branchScope->orWhereNotNull('branch_id');
                    }
                });

                $visible->where(function (Builder $contactScope) use ($user) {
                    $contactScope->whereNull('contact_id');
                    if ($user->can('contacts.read')) {
                        $contactScope->orWhereNotNull('contact_id');
                    }
                });

                $visible->where(function (Builder $deptScope) use ($department) {
                    $deptScope->whereNull('department');
                    if ($department) {
                        $deptScope->orWhere('department', $department);
                    }
                });

                $visible->where(function (Builder $companyScope) use ($user) {
                    $companyScope->where('category', '!=', DocumentCategory::Company->value)
                        ->orWhereNull('category');
                    if ($user->can('settings.read')) {
                        $companyScope->orWhere('category', DocumentCategory::Company->value);
                    }
                });
            });
        });
    }

    public function userDepartment(User $user): ?string
    {
        return Cache::remember('scf:dms:user-dept:'.$user->id, config('documents.cache_ttl', 300), function () use ($user) {
            if (! $user->email) {
                return null;
            }

            return Employee::query()->where('email', $user->email)->value('department');
        });
    }
}
