<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $category_id
 * @property string $name
 * @property string $sku
 * @property string|null $barcode
 * @property string|null $description
 * @property string $purchase_price
 * @property string $sale_price
 * @property int $stock_quantity
 * @property int $minimum_stock_level
 * @property bool $is_active
 * @property bool $is_favorite
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'category_id',
    'name',
    'sku',
    'barcode',
    'description',
    'purchase_price',
    'sale_price',
    'stock_quantity',
    'minimum_stock_level',
    'is_active',
    'is_favorite',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'minimum_stock_level' => 'integer',
            'is_active' => 'boolean',
            'is_favorite' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * @return HasMany<Variant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    /**
     * @return HasMany<BillOfMaterial, $this>
     */
    public function billOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class);
    }

    /**
     * @return HasMany<BillOfMaterial, $this>
     */
    public function componentBillOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'component_product_id');
    }

    /**
     * @return HasMany<StockTransfer, $this>
     */
    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class);
    }

    /**
     * @return HasMany<InventoryAdjustment, $this>
     */
    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    /**
     * @return HasMany<ProductionOrder, $this>
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    /**
     * @return HasMany<QualityControl, $this>
     */
    public function qualityControls(): HasMany
    {
        return $this->hasMany(QualityControl::class);
    }

    /**
     * @return HasMany<QuotationLine, $this>
     */
    public function quotationLines(): HasMany
    {
        return $this->hasMany(QuotationLine::class);
    }

    /**
     * @return HasMany<SaleOrderLine, $this>
     */
    public function saleOrderLines(): HasMany
    {
        return $this->hasMany(SaleOrderLine::class);
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * @return HasMany<PurchaseRequestLine, $this>
     */
    public function purchaseRequestLines(): HasMany
    {
        return $this->hasMany(PurchaseRequestLine::class);
    }

    /**
     * @return HasMany<RfqLine, $this>
     */
    public function rfqLines(): HasMany
    {
        return $this->hasMany(RfqLine::class);
    }

    /**
     * @return HasMany<PurchaseOrderLine, $this>
     */
    public function purchaseOrderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /**
     * @return HasMany<BillLine, $this>
     */
    public function billLines(): HasMany
    {
        return $this->hasMany(BillLine::class);
    }

    /**
     * @return HasMany<PosSaleItem, $this>
     */
    public function posSaleItems(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }

    /**
     * @return HasMany<PosFavorite, $this>
     */
    public function posFavorites(): HasMany
    {
        return $this->hasMany(PosFavorite::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->minimum_stock_level;
    }
}
