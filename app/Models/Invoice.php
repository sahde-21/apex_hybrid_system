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
 * @property string|null $notes
 * @property string $source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact|null $contact
 * @property-read SaleOrder|null $saleOrder
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
    'notes',
    'source',
])]
class Invoice extends Model
{
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'subtotal_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
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
}
