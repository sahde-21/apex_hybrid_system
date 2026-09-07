<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int|null $contact_id
 * @property int|null $invoice_id
 * @property int|null $bill_id
 * @property int|null $gift_card_id
 * @property Carbon $payment_date
 * @property string $amount
 * @property PaymentType $type
 * @property PaymentStatus $status
 * @property string|null $payment_method
 * @property string|null $account_label
 * @property string|null $notes
 * @property Carbon|null $posted_at
 * @property int|null $posted_by
 * @property Carbon|null $reversed_at
 * @property int|null $reversed_by
 * @property int|null $reversal_of_id
 * @property string|null $reversal_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact|null $contact
 * @property-read Invoice|null $invoice
 * @property-read Bill|null $bill
 * @property-read GiftCard|null $giftCard
 * @property-read User|null $postedBy
 * @property-read User|null $reversedBy
 * @property-read Payment|null $reversalOf
 * @property-read Collection<int, SalesDocumentEvent> $events
 */
#[Fillable([
    'reference_number',
    'contact_id',
    'invoice_id',
    'bill_id',
    'gift_card_id',
    'payment_date',
    'amount',
    'type',
    'status',
    'payment_method',
    'account_label',
    'notes',
    'posted_at',
    'posted_by',
    'reversed_at',
    'reversed_by',
    'reversal_of_id',
    'reversal_reason',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'type' => PaymentType::class,
            'status' => PaymentStatus::class,
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
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
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Bill, $this>
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * @return BelongsTo<GiftCard, $this>
     */
    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    /**
     * @return MorphMany<SalesDocumentEvent, $this>
     */
    public function events(): MorphMany
    {
        return $this->morphMany(SalesDocumentEvent::class, 'document')->latest('created_at');
    }
}
