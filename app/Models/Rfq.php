<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\RfqStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int|null $purchase_request_id
 * @property Carbon $rfq_date
 * @property Carbon|null $valid_until
 * @property RfqStatus $status
 * @property string $subtotal_amount
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property string $currency_code
 * @property string|null $notes
 * @property string|null $terms
 * @property int|null $selected_vendor_id
 * @property int|null $converted_purchase_order_id
 * @property Carbon|null $converted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseRequest|null $purchaseRequest
 * @property-read Contact|null $selectedVendor
 * @property-read PurchaseOrder|null $convertedPurchaseOrder
 */
#[Fillable([
    'reference_number',
    'purchase_request_id',
    'rfq_date',
    'valid_until',
    'status',
    'subtotal_amount',
    'discount_amount',
    'tax_amount',
    'total_amount',
    'currency_code',
    'notes',
    'terms',
    'selected_vendor_id',
    'converted_purchase_order_id',
    'converted_at',
])]
class Rfq extends Model
{
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rfq_date' => 'date',
            'valid_until' => 'date',
            'status' => RfqStatus::class,
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'converted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PurchaseRequest, $this>
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function selectedVendor(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'selected_vendor_id');
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function convertedPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'converted_purchase_order_id');
    }

    /**
     * @return HasMany<RfqLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(RfqLine::class)->orderBy('line_number');
    }

    /**
     * @return HasMany<RfqVendor, $this>
     */
    public function vendors(): HasMany
    {
        return $this->hasMany(RfqVendor::class);
    }

    /**
     * @return MorphMany<SalesDocumentEvent, $this>
     */
    public function events(): MorphMany
    {
        return $this->morphMany(SalesDocumentEvent::class, 'document')->latest('created_at');
    }
}
