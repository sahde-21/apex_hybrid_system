<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'reference_number',
    'contact_id',
    'sale_order_id',
    'invoice_date',
    'due_date',
    'status',
    'subtotal_amount',
    'total_amount',
    'tax_amount',
    'discount_amount',
    'paid_amount',
    'currency_code',
    'payment_terms',
    'issued_at',
    'voided_at',
    'voided_by',
    'void_reason',
    'notes',
    'source',
])]
/**
 * @property InvoiceStatus $status
 */
class Invoice extends Model
{
    use Auditable, HasFactory;

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'voided_at' => 'datetime',
            'status' => InvoiceStatus::class,
            'subtotal_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('line_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function posSale(): HasOne
    {
        return $this->hasOne(PosSale::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function events(): MorphMany
    {
        return $this->morphMany(SalesDocumentEvent::class, 'document')->latest('created_at');
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->total_amount - (float) $this->paid_amount, 2));
    }
}
