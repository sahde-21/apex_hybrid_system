<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\QualityControlStatus;
use Database\Factories\QualityControlFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int|null $production_order_id
 * @property int $product_id
 * @property Carbon $inspection_date
 * @property QualityControlStatus $status
 * @property int $passed_quantity
 * @property int $failed_quantity
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'reference_number',
    'production_order_id',
    'product_id',
    'inspection_date',
    'status',
    'passed_quantity',
    'failed_quantity',
    'notes',
    'created_by',
    'updated_by',
])]
class QualityControl extends Model
{
    /** @use HasFactory<QualityControlFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'status' => QualityControlStatus::class,
            'passed_quantity' => 'integer',
            'failed_quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductionOrder, $this>
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
