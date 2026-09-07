<?php

namespace App\Models;

use App\Enums\PurchaseReturnStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $purchase_order_id
 * @property int|null $warehouse_id
 * @property PurchaseReturnStatus $status
 * @property Carbon|null $returned_at
 * @property int|null $returned_by
 * @property string|null $idempotency_key
 * @property string|null $notes
 */
#[Fillable([
    'reference_number',
    'purchase_order_id',
    'warehouse_id',
    'status',
    'returned_at',
    'returned_by',
    'idempotency_key',
    'notes',
])]
class PurchaseReturn extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseReturnStatus::class,
            'returned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function returnedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /**
     * @return HasMany<PurchaseReturnLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLine::class);
    }
}
