<?php

namespace App\Concerns;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::creating(function (Model $model): void {
            $userId = static::staffUserId();
            if ($model->isFillable('created_by') && $userId) {
                $model->created_by = $userId;
            }
        });

        static::updating(function (Model $model): void {
            $userId = static::staffUserId();
            if ($model->isFillable('updated_by') && $userId) {
                $model->updated_by = $userId;
            }
        });

        static::created(function (Model $model): void {
            static::recordAudit($model, 'created');
        });

        static::updated(function (Model $model): void {
            if ($model->wasChanged()) {
                static::recordAudit($model, 'updated', $model->getChanges(), $model->getOriginal());
            }
        });

        static::deleted(function (Model $model): void {
            static::recordAudit($model, 'deleted');
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model): void {
                static::recordAudit($model, 'restored');
            });
        }
    }

    /**
     * Only staff User IDs may be stored on audit/created_by columns.
     * Portal customers authenticate on a separate guard and must not write FK user_id values.
     */
    protected static function staffUserId(): ?int
    {
        $user = Auth::guard('web')->user() ?? Auth::user();

        return $user instanceof User ? (int) $user->getAuthIdentifier() : null;
    }

    /**
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $oldValues
     */
    protected static function recordAudit(Model $model, string $action, ?array $newValues = null, ?array $oldValues = null): void
    {
        if (! class_exists(AuditLog::class)) {
            return;
        }

        AuditLog::query()->create([
            'user_id' => static::staffUserId(),
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => static::redactAuditValues($model, $oldValues),
            'new_values' => static::redactAuditValues($model, $newValues ?? ($action === 'created' ? $model->getAttributes() : null)),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    protected static function redactAuditValues(Model $model, ?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $hidden = method_exists($model, 'getHidden') ? $model->getHidden() : [];
        $sensitive = array_unique(array_merge(
            $hidden,
            config('security.audit_redact', [])
        ));

        foreach ($sensitive as $attribute) {
            if (array_key_exists($attribute, $values)) {
                $values[$attribute] = '[redacted]';
            }
        }

        return $values;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
