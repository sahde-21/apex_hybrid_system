<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int|null $product_id
 * @property int|null $rfq_line_id
 * @property int $line_number
 * @property string $description
 * @property string $quantity
 * @property string $unit_price
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $line_total
 * @property string $quantity_billed
 * @property string $quantity_received
 * @property string $quantity_returned
 */
#[Fillable([
    'purchase_order_id',
    'product_id',
    'rfq_line_id',
    'line_number',
    'description',
    'quantity',
    'unit_price',
    'discount_amount',
    'tax_amount',
    'line_total',
    'quantity_billed',
    'quantity_received',
    'quantity_returned',
])]
class PurchaseOrderLine extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity_billed' => 'decimal:4',
            'quantity_received' => 'decimal:4',
            'quantity_returned' => 'decimal:4',
            'line_number' => 'integer',
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
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<RfqLine, $this>
     */
    public function rfqLine(): BelongsTo
    {
        return $this->belongsTo(RfqLine::class);
    }

    public function quantityRemainingToBill(): float
    {
        return max(0, (float) $this->quantity - (float) $this->quantity_billed);
    }

    public function quantityRemainingToReceive(): float
    {
        return max(0, (float) $this->quantity - (float) $this->quantity_received);
    }

    public function quantityRemainingToReturn(): float
    {
        return max(0, (float) $this->quantity_received - (float) $this->quantity_returned);
    }
}
