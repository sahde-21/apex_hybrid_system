<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\SupplierShipmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $contact_id
 * @property int $purchase_order_id
 * @property Carbon|null $scheduled_date
 * @property Carbon|null $shipped_at
 * @property string|null $carrier
 * @property string|null $tracking_number
 * @property SupplierShipmentStatus $status
 * @property string|null $notes
 */
#[Fillable([
    'reference_number',
    'contact_id',
    'purchase_order_id',
    'scheduled_date',
    'shipped_at',
    'carrier',
    'tracking_number',
    'status',
    'notes',
])]
class SupplierShipment extends Model
{
    use Auditable, HasFactory;

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'shipped_at' => 'datetime',
            'status' => SupplierShipmentStatus::class,
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
}
