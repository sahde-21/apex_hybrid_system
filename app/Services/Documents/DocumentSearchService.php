<?php

namespace App\Services\Documents;

use App\Models\ManagedDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentSearchService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ManagedDocument>
     */
    public function search(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ManagedDocument::query()
            ->with(['folder', 'owner:id,name', 'branch:id,name'])
            ->when(($filters['trashed'] ?? false) === true, fn ($q) => $q->onlyTrashed(), fn ($q) => $q->whereNull('deleted_at'));

        $this->applyAccessScope($query, $user);
        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($perPage);
    }

    /**
     * @param  Builder<ManagedDocument>  $query
     */
    protected function applyAccessScope(Builder $query, User $user): void
    {
        app(DocumentAccessService::class)->applyListScope($query, $user);
    }

    /**
     * @param  Builder<ManagedDocument>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        } elseif (array_key_exists('folder_id', $filters) && $filters['folder_id'] === null) {
            $query->whereNull('folder_id');
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $tag = $filters['q'];
            $query->where(function (Builder $q) use ($term, $tag) {
                $q->where('name', 'like', $term)
                    ->orWhere('original_name', 'like', $term)
                    ->orWhereJsonContains('tags', $tag);
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if (! empty($filters['tag'])) {
            $query->whereJsonContains('tags', $filters['tag']);
        }
    }
}
