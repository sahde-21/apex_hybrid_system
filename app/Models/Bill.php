<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\BillStatus;
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
 * @property int|null $contact_id
 * @property int|null $purchase_order_id
 * @property Carbon $bill_date
 * @property Carbon|null $due_date
 * @property BillStatus $status
 * @property string $subtotal_amount
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property string $paid_amount
 * @property string $currency_code
 * @property Carbon|null $issued_at
 * @property Carbon|null $voided_at
 * @property int|null $voided_by
 * @property string|null $void_reason
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact|null $contact
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read User|null $voidedBy
 */
#[Fillable([
    'reference_number',
    'contact_id',
    'purchase_order_id',
    'bill_date',
    'due_date',
    'status',
    'subtotal_amount',
    'discount_amount',
    'tax_amount',
    'total_amount',
    'paid_amount',
    'currency_code',
    'issued_at',
    'voided_at',
    'voided_by',
    'void_reason',
    'notes',
])]
class Bill extends Model
{
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'status' => BillStatus::class,
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'voided_at' => 'datetime',
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
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return HasMany<BillLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(BillLine::class)->orderBy('line_number');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
