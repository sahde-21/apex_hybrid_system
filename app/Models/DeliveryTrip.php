<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\DeliveryTripStatus;
use Database\Factories\DeliveryTripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int|null $shipping_method_id
 * @property string|null $driver_name
 * @property string|null $vehicle_plate
 * @property Carbon $trip_date
 * @property DeliveryTripStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'reference_number',
    'shipping_method_id',
    'driver_name',
    'vehicle_plate',
    'trip_date',
    'status',
    'notes',
    'created_by',
    'updated_by',
])]
class DeliveryTrip extends Model
{
    /** @use HasFactory<DeliveryTripFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
            'status' => DeliveryTripStatus::class,
        ];
    }

    /**
     * @return BelongsTo<ShippingMethod, $this>
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }
}
