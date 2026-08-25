<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\StockTransferStatus;
use Database\Factories\StockTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $product_id
 * @property int|null $variant_id
 * @property int $from_warehouse_id
 * @property int $to_warehouse_id
 * @property int $quantity
 * @property Carbon $transfer_date
 * @property StockTransferStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $approved_by
 * @property int|null $shipped_by
 * @property int|null $received_by
 * @property int|null $cancelled_by
 * @property Carbon|null $approved_at
 * @property Carbon|null $shipped_at
 * @property Carbon|null $received_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'reference_number',
    'product_id',
    'variant_id',
    'from_warehouse_id',
    'to_warehouse_id',
    'quantity',
    'transfer_date',
    'status',
    'notes',
    'created_by',
    'updated_by',
    'approved_by',
    'shipped_by',
    'received_by',
    'cancelled_by',
    'approved_at',
    'shipped_at',
    'received_at',
    'cancelled_at',
])]
class StockTransfer extends Model
{
    /** @use HasFactory<StockTransferFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'transfer_date' => 'date',
            'status' => StockTransferStatus::class,
            'approved_at' => 'datetime',
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
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
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}
