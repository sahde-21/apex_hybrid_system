<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryAdjustmentStatus;
use Database\Factories\InventoryAdjustmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $product_id
 * @property int|null $variant_id
 * @property int|null $warehouse_id
 * @property Carbon $adjustment_date
 * @property int $quantity_change
 * @property string $reason
 * @property string|null $notes
 * @property InventoryAdjustmentStatus $status
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property int|null $posted_by
 * @property int|null $cancelled_by
 * @property Carbon|null $approved_at
 * @property Carbon|null $posted_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Variant|null $variant
 * @property-read Warehouse|null $warehouse
 */
#[Fillable([
    'reference_number',
    'product_id',
    'variant_id',
    'warehouse_id',
    'adjustment_date',
    'quantity_change',
    'reason',
    'notes',
    'status',
    'created_by',
    'approved_by',
    'posted_by',
    'cancelled_by',
    'approved_at',
    'posted_at',
    'cancelled_at',
])]
class InventoryAdjustment extends Model
{
    /** @use HasFactory<InventoryAdjustmentFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'quantity_change' => 'integer',
            'status' => InventoryAdjustmentStatus::class,
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function reasonEnum(): ?InventoryAdjustmentReason
    {
        return InventoryAdjustmentReason::tryFrom($this->reason);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Variant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
