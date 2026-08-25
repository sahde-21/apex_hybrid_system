<?php

namespace App\Models;

use Database\Factories\StockLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $warehouse_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property int $on_hand
 * @property int $reserved
 * @property string|null $average_cost
 * @property int|null $minimum_level
 * @property int $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'warehouse_id',
    'product_id',
    'variant_id',
    'on_hand',
    'reserved',
    'average_cost',
    'minimum_level',
    'version',
])]
class StockLevel extends Model
{
    /** @use HasFactory<StockLevelFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'on_hand' => 'integer',
            'reserved' => 'integer',
            'average_cost' => 'decimal:4',
            'minimum_level' => 'integer',
            'version' => 'integer',
        ];
    }

    public function available(): int
    {
        return $this->on_hand - $this->reserved;
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
}
