<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_order_id',
    'product_id',
    'quotation_line_id',
    'line_number',
    'description',
    'quantity',
    'unit_price',
    'discount_amount',
    'tax_amount',
    'line_total',
    'quantity_invoiced',
    'quantity_fulfilled',
])]
class SaleOrderLine extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity_invoiced' => 'decimal:4',
            'quantity_fulfilled' => 'decimal:4',
            'line_number' => 'integer',
        ];
    }

    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function quotationLine(): BelongsTo
    {
        return $this->belongsTo(QuotationLine::class);
    }

    public function quantityRemainingToInvoice(): float
    {
        return max(0, (float) $this->quantity - (float) $this->quantity_invoiced);
    }

    public function recalculateTotal(): void
    {
        $this->line_total = round(
            ((float) $this->quantity * (float) $this->unit_price) - (float) $this->discount_amount + (float) $this->tax_amount,
            2
        );
    }
}
