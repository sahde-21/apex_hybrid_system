<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\QuotationStatus;
use Database\Factories\QuotationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int|null $contact_id
 * @property Carbon $quotation_date
 * @property Carbon|null $valid_until
 * @property QuotationStatus $status
 * @property string $subtotal_amount
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property string $currency_code
 * @property string|null $notes
 * @property string|null $terms
 * @property int|null $converted_sale_order_id
 * @property Carbon|null $converted_at
 * @property int|null $salesperson_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact|null $contact
 * @property-read Collection<int, QuotationLine> $lines
 * @property-read SaleOrder|null $convertedSaleOrder
 * @property-read User|null $salesperson
 * @property-read Collection<int, SalesDocumentEvent> $events
 */
#[Fillable([
    'reference_number',
    'contact_id',
    'quotation_date',
    'valid_until',
    'status',
    'subtotal_amount',
    'discount_amount',
    'tax_amount',
    'total_amount',
    'currency_code',
    'notes',
    'terms',
    'converted_sale_order_id',
    'converted_at',
    'salesperson_id',
])]
class Quotation extends Model
{
    /** @use HasFactory<QuotationFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'converted_at' => 'datetime',
            'status' => QuotationStatus::class,
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
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
     * @return HasMany<QuotationLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class)->orderBy('line_number');
    }

    /**
     * @return BelongsTo<SaleOrder, $this>
     */
    public function convertedSaleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class, 'converted_sale_order_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    /**
     * @return MorphMany<SalesDocumentEvent, $this>
     */
    public function events(): MorphMany
    {
        return $this->morphMany(SalesDocumentEvent::class, 'document')->latest('created_at');
    }
}
