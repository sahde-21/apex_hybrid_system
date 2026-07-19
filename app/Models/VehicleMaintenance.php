<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\VehicleMaintenanceStatus;
use Database\Factories\VehicleMaintenanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $vehicle_plate
 * @property Carbon $maintenance_date
 * @property string $maintenance_type
 * @property string $cost
 * @property VehicleMaintenanceStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'vehicle_plate',
    'maintenance_date',
    'maintenance_type',
    'cost',
    'status',
    'notes',
    'created_by',
    'updated_by',
])]
class VehicleMaintenance extends Model
{
    /** @use HasFactory<VehicleMaintenanceFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'maintenance_date' => 'date',
            'cost' => 'decimal:2',
            'status' => VehicleMaintenanceStatus::class,
        ];
    }
}
