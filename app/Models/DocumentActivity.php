<?php

namespace App\Models;

use App\Enums\DocumentActivityAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $managed_document_id
 * @property int|null $user_id
 * @property DocumentActivityAction|string $action
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'managed_document_id',
    'user_id',
    'action',
    'ip_address',
    'user_agent',
    'metadata',
    'created_at',
])]
class DocumentActivity extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => DocumentActivityAction::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
