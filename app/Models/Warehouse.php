<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $address
 * @property string|null $phone
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'code',
    'address',
    'phone',
    'is_active',
])]
class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * @return HasMany<SaleOrder, $this>
     */
    public function saleOrders(): HasMany
    {
        return $this->hasMany(SaleOrder::class);
    }

    /**
     * @return HasMany<ProductionOrder, $this>
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    /**
     * @return HasMany<InventoryAdjustment, $this>
     */
    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    /**
     * @return HasMany<FloorPlan, $this>
     */
    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class);
    }

    /**
     * @return HasMany<PosRegister, $this>
     */
    public function posRegisters(): HasMany
    {
        return $this->hasMany(PosRegister::class);
    }

    /**
     * @return HasMany<StockTransfer, $this>
     */
    public function outgoingStockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    /**
     * @return HasMany<StockTransfer, $this>
     */
    public function incomingStockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }
}
