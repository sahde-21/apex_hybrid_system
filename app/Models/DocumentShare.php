<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

/**
 * @property int $id
 * @property int $managed_document_id
 * @property string $token
 * @property string|null $password
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int|null $download_limit
 * @property int $download_count
 * @property bool $is_active
 */
#[Fillable([
    'managed_document_id',
    'token',
    'password',
    'expires_at',
    'download_limit',
    'download_count',
    'is_active',
    'created_by',
])]
class DocumentShare extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'download_limit' => 'integer',
            'download_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ManagedDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ManagedDocument::class, 'managed_document_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isDownloadLimitReached(): bool
    {
        return $this->download_limit !== null && $this->download_count >= $this->download_limit;
    }

    public function isAccessible(): bool
    {
        return $this->is_active && ! $this->isExpired() && ! $this->isDownloadLimitReached();
    }

    public function checkPassword(?string $password): bool
    {
        if ($this->password === null) {
            return true;
        }

        return $password !== null && Hash::check($password, $this->password);
    }
}
