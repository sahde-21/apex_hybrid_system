<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\SaleOrderStatus;
use Database\Factories\SaleOrderFactory;
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
 * @property int|null $quotation_id
 * @property int|null $warehouse_id
 * @property int|null $branch_id
 * @property int|null $salesperson_id
 * @property Carbon $order_date
 * @property Carbon|null $delivery_date
 * @property SaleOrderStatus $status
 * @property string $subtotal_amount
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property string $currency_code
 * @property string|null $notes
 * @property string|null $billing_address
 * @property string|null $shipping_address
 * @property string|null $terms
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact|null $contact
 * @property-read Warehouse|null $warehouse
 * @property-read Branch|null $branch
 * @property-read Quotation|null $quotation
 * @property-read User|null $salesperson
 * @property-read Collection<int, SaleOrderLine> $lines
 * @property-read Collection<int, Invoice> $invoices
 * @property-read Collection<int, SalesDocumentEvent> $events
 */
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
class SaleOrder extends Model
{
    /** @use HasFactory<SaleOrderFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
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

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Quotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    /**
     * @return HasMany<SaleOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SaleOrderLine::class)->orderBy('line_number');
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return MorphMany<SalesDocumentEvent, $this>
     */
    public function events(): MorphMany
    {
        return $this->morphMany(SalesDocumentEvent::class, 'document')->latest('created_at');
    }
}
