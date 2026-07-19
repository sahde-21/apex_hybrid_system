<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\SaleOrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'reference_number',
    'contact_id',
    'quotation_id',
    'warehouse_id',
    'branch_id',
    'salesperson_id',
    'order_date',
    'delivery_date',
    'status',
    'subtotal_amount',
    'discount_amount',
    'tax_amount',
    'total_amount',
    'currency_code',
    'notes',
    'billing_address',
    'shipping_address',
    'terms',
])]
/**
 * @property SaleOrderStatus $status
 */
class SaleOrder extends Model
{
    use Auditable, HasFactory;

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'delivery_date' => 'date',
            'status' => SaleOrderStatus::class,
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleOrderLine::class)->orderBy('line_number');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function events(): MorphMany
    {
        return $this->morphMany(SalesDocumentEvent::class, 'document')->latest('created_at');
    }
}
