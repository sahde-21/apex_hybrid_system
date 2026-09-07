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
            if (in_array('created_by', $model->getFillable(), true) && $userId) {
                // setAttribute preserves Eloquent attribute assignment without assuming
                // every Auditable model declares $created_by as a typed property.
                $model->setAttribute('created_by', $userId);
            }
        });

        static::updating(function (Model $model): void {
            $userId = static::staffUserId();
            if (in_array('updated_by', $model->getFillable(), true) && $userId) {
                $model->setAttribute('updated_by', $userId);
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

        // SoftDeletes::restored() is a thin wrapper around registerModelEvent('restored').
        // Call registerModelEvent directly so PHPStan does not require SoftDeletes on every
        // Auditable consumer, while preserving the same runtime registration when SoftDeletes is present.
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::registerModelEvent('restored', function (Model $model): void {
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

        // Model::getHidden() is part of the Eloquent Model contract.
        $sensitive = array_unique(array_merge(
            $model->getHidden(),
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
