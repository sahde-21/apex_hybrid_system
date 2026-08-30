<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int|null $contact_id
 * @property int|null $sale_order_id
 * @property Carbon $invoice_date
 * @property Carbon|null $due_date
 * @property InvoiceStatus $status
 * @property string $subtotal_amount
 * @property string $total_amount
 * @property string $tax_amount
 * @property string $discount_amount
 * @property string $paid_amount
 * @property string $currency_code
 * @property string|null $payment_terms
 * @property Carbon|null $issued_at
 * @property Carbon|null $voided_at
 * @property int|null $voided_by
 * @property string|null $void_reason
 * @property string|null $notes
 * @property string|null $source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact|null $contact
 * @property-read SaleOrder|null $saleOrder
 * @property-read User|null $voidedBy
 * @property-read Collection<int, InvoiceLine> $lines
 * @property-read Collection<int, Payment> $payments
 * @property-read PosSale|null $posSale
 * @property-read Collection<int, SalesDocumentEvent> $events
 */
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
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
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

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<SaleOrder, $this>
     */
    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class);
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('line_number');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<PosSale, $this>
     */
    public function posSale(): HasOne
    {
        return $this->hasOne(PosSale::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * @return MorphMany<SalesDocumentEvent, $this>
     */
    public function events(): MorphMany
    {
        return $this->morphMany(SalesDocumentEvent::class, 'document')->latest('created_at');
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->total_amount - (float) $this->paid_amount, 2));
    }
}
