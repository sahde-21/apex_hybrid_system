<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/**
 * Read-only audit log access. Mutations are intentionally unsupported.
 */
class AuditLogService
{
    /**
     * @param  array{search?: string, action?: string, user_id?: int|null, model?: string|null, date_from?: string|null, date_to?: string|null}  $filters
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function paginate(User $viewer, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        abort_unless($viewer->can('audit-logs.read'), 403);

        return $this->query($filters)
            ->with(['user', 'auditable'])
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function find(User $viewer, int $id): AuditLog
    {
        abort_unless($viewer->can('audit-logs.read'), 403);

        return AuditLog::query()->with(['user', 'auditable'])->findOrFail($id);
    }

    /**
     * @param  array{search?: string, action?: string, user_id?: int|null, model?: string|null, date_from?: string|null, date_to?: string|null}  $filters
     * @return Builder<AuditLog>
     */
    public function query(array $filters = []): Builder
    {
        return AuditLog::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('auditable_type', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($filters['user_id'] ?? null, fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($filters['model'] ?? null, function ($q, $model) {
                $q->where('auditable_type', 'like', '%'.class_basename($model).'%');
            })
            ->when($filters['date_from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to));
    }

    public function update(): never
    {
        throw new LogicException(__('scf.activity.audit_immutable'));
    }

    public function delete(): never
    {
        throw new LogicException(__('scf.activity.audit_immutable'));
    }
}
