<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\PurchaseRequestStatus;
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
 * @property int|null $requester_id
 * @property string|null $department
 * @property Carbon $request_date
 * @property Carbon|null $needed_by
 * @property PurchaseRequestStatus $status
 * @property string $subtotal_amount
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property string $currency_code
 * @property string|null $notes
 * @property array|null $attachments
 * @property int|null $converted_rfq_id
 * @property Carbon|null $converted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $requester
 * @property-read Rfq|null $convertedRfq
 */
#[Fillable([
    'reference_number',
    'requester_id',
    'department',
    'request_date',
    'needed_by',
    'status',
    'subtotal_amount',
    'discount_amount',
    'tax_amount',
    'total_amount',
    'currency_code',
    'notes',
    'attachments',
    'converted_rfq_id',
    'converted_at',
])]
class PurchaseRequest extends Model
{
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'needed_by' => 'date',
            'status' => PurchaseRequestStatus::class,
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'attachments' => 'array',
            'converted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<Rfq, $this>
     */
    public function convertedRfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class, 'converted_rfq_id');
    }

    /**
     * @return HasMany<PurchaseRequestLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequestLine::class)->orderBy('line_number');
    }

    /**
     * @return MorphMany<SalesDocumentEvent, $this>
     */
    public function events(): MorphMany
    {
        return $this->morphMany(SalesDocumentEvent::class, 'document')->latest('created_at');
    }
}
