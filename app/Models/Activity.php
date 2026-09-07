<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Enums\ActivityVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property ActivityType $type
 * @property string|null $event_key
 * @property int|null $user_id
 * @property string|null $title
 * @property string|null $body
 * @property ActivityVisibility $visibility
 * @property array<string, mixed>|null $metadata
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $related_type
 * @property int|null $related_id
 * @property int|null $parent_id
 * @property int|null $managed_document_id
 * @property bool $is_system
 * @property Carbon|null $edited_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'subject_type',
    'subject_id',
    'type',
    'event_key',
    'user_id',
    'title',
    'body',
    'visibility',
    'metadata',
    'old_values',
    'new_values',
    'related_type',
    'related_id',
    'parent_id',
    'managed_document_id',
    'is_system',
    'edited_at',
])]
class Activity extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'visibility' => ActivityVisibility::class,
            'metadata' => 'array',
            'old_values' => 'array',
            'new_values' => 'array',
            'is_system' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->latest();
    }

    /**
     * @return HasMany<ActivityMention, $this>
     */
    public function mentions(): HasMany
    {
        return $this->hasMany(ActivityMention::class);
    }

    /**
     * @return BelongsTo<ManagedDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ManagedDocument::class, 'managed_document_id');
    }

    public function isEditableBy(User $user): bool
    {
        if ($this->is_system || ! $this->type->isUserGenerated()) {
            return false;
        }

        if ($user->can('activities.manage')) {
            return true;
        }

        return $this->user_id === $user->id && $user->can('activities.edit_own');
    }

    public function isDeletableBy(User $user): bool
    {
        if ($this->is_system || ! $this->type->isUserGenerated()) {
            return false;
        }

        if ($user->can('activities.manage')) {
            return true;
        }

        return $this->user_id === $user->id && $user->can('activities.delete_own');
    }
}
