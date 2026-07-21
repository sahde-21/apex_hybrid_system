<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $action
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'auditable_type',
    'auditable_id',
    'action',
    'old_values',
    'new_values',
    'ip_address',
    'user_agent',
])]
class AuditLog extends Model
{
    public $timestamps = true;

    const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \LogicException('Audit logs are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new \LogicException('Audit logs are immutable and cannot be deleted.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Meaningful field diffs excluding technical noise.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function meaningfulChanges(): array
    {
        $ignored = config('activity.ignored_audit_fields', [
            'updated_at', 'created_at', 'deleted_at', 'remember_token',
        ]);

        $old = collect($this->old_values ?? [])->except($ignored);
        $new = collect($this->new_values ?? [])->except($ignored);
        $keys = $old->keys()->merge($new->keys())->unique();

        $changes = [];
        foreach ($keys as $key) {
            $changes[$key] = [
                'old' => $old->get($key),
                'new' => $new->get($key),
            ];
        }

        return $changes;
    }
}
