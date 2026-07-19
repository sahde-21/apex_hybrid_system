<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $portal_customer_id
 * @property string $type
 * @property string $title
 * @property string|null $body
 * @property string|null $action_url
 * @property Carbon|null $read_at
 */
#[Fillable([
    'portal_customer_id',
    'type',
    'title',
    'body',
    'action_url',
    'read_at',
])]
class PortalNotification extends Model
{
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PortalCustomer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(PortalCustomer::class, 'portal_customer_id');
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
