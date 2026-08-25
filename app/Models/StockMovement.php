<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Exceptions\Inventory\StockMovementImmutableException;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $warehouse_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property int $quantity
 * @property int $quantity_before
 * @property int $quantity_after
 * @property int $reserved_delta
 * @property StockMovementType $movement_type
 * @property string|null $reason_code
 * @property Carbon $occurred_at
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property int|null $reference_line_id
 * @property string $idempotency_key
 * @property string|null $unit_cost
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 */
#[Fillable([
    'uuid',
    'warehouse_id',
    'product_id',
    'variant_id',
    'quantity',
    'quantity_before',
    'quantity_after',
    'reserved_delta',
    'movement_type',
    'reason_code',
    'occurred_at',
    'reference_type',
    'reference_id',
    'reference_line_id',
    'idempotency_key',
    'unit_cost',
    'notes',
    'created_by',
    'created_at',
])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (StockMovement $movement): void {
            if (! isset($movement->attributes['uuid']) || $movement->attributes['uuid'] === '') {
                $movement->uuid = (string) Str::uuid();
            }

            if (! isset($movement->attributes['created_at'])) {
                $movement->setAttribute('created_at', Carbon::now());
            }
        });

        static::updating(function (): void {
            throw new StockMovementImmutableException;
        });

        static::deleting(function (): void {
            throw new StockMovementImmutableException;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'reserved_delta' => 'integer',
            'movement_type' => StockMovementType::class,
            'occurred_at' => 'datetime',
            'unit_cost' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
