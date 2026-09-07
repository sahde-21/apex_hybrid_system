<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SupplierResponseStatus;
use Database\Factories\PurchaseOrderFactory;
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
 * @property int|null $rfq_id
 * @property int|null $purchase_request_id
 * @property int|null $warehouse_id
 * @property int|null $branch_id
 * @property int|null $buyer_id
 * @property Carbon $order_date
 * @property Carbon|null $expected_date
 * @property PurchaseOrderStatus $status
 * @property SupplierResponseStatus|null $supplier_response
 * @property string|null $supplier_comment
 * @property Carbon|null $supplier_responded_at
 * @property string $subtotal_amount
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property string $currency_code
 * @property string|null $notes
 * @property string|null $terms
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact|null $contact
 * @property-read Rfq|null $rfq
 * @property-read PurchaseRequest|null $purchaseRequest
 * @property-read Warehouse|null $warehouse
 * @property-read Branch|null $branch
 * @property-read User|null $buyer
 */
#[Fillable([
    'reference_number',
    'contact_id',
    'rfq_id',
    'purchase_request_id',
    'warehouse_id',
    'branch_id',
    'buyer_id',
    'order_date',
    'expected_date',
    'status',
    'supplier_response',
    'supplier_comment',
    'supplier_responded_at',
    'subtotal_amount',
    'discount_amount',
    'tax_amount',
    'total_amount',
    'currency_code',
    'notes',
    'terms',
])]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'status' => PurchaseOrderStatus::class,
            'supplier_response' => SupplierResponseStatus::class,
            'supplier_responded_at' => 'datetime',
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
     * @return BelongsTo<Rfq, $this>
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    /**
     * @return BelongsTo<PurchaseRequest, $this>
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
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
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * @return HasMany<PurchaseOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('line_number');
    }

    /**
     * @return HasMany<Bill, $this>
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * @return HasMany<PurchaseReceipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    /**
     * @return HasMany<PurchaseReturn, $this>
     */
    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * @return HasMany<SupplierShipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(SupplierShipment::class);
    }

    /**
     * @return MorphMany<SalesDocumentEvent, $this>
     */
    public function events(): MorphMany
    {
        return $this->morphMany(SalesDocumentEvent::class, 'document')->latest('created_at');
    }
}
