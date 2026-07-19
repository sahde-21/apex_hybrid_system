<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SupplierResponseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int|null $contact_id
 * @property int|null $warehouse_id
 * @property Carbon $order_date
 * @property Carbon|null $expected_date
 * @property PurchaseOrderStatus $status
 * @property SupplierResponseStatus|null $supplier_response
 * @property string|null $supplier_comment
 * @property Carbon|null $supplier_responded_at
 * @property string $total_amount
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact|null $contact
 * @property-read Warehouse|null $warehouse
 */
#[Fillable([
    'reference_number',
    'contact_id',
    'warehouse_id',
    'order_date',
    'expected_date',
    'status',
    'supplier_response',
    'supplier_comment',
    'supplier_responded_at',
    'total_amount',
    'notes',
])]
class PurchaseOrder extends Model
{
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
     * @return HasMany<SupplierShipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(SupplierShipment::class);
    }
}
