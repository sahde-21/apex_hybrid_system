<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bill_id
 * @property int|null $product_id
 * @property int|null $purchase_order_line_id
 * @property int $line_number
 * @property string $description
 * @property string $quantity
 * @property string $unit_price
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $line_total
 */
#[Fillable([
    'bill_id',
    'product_id',
    'purchase_order_line_id',
    'line_number',
    'description',
    'quantity',
    'unit_price',
    'discount_amount',
    'tax_amount',
    'line_total',
])]
class BillLine extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'line_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Bill, $this>
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<PurchaseOrderLine, $this>
     */
    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }
}
