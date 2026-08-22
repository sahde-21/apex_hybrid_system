<?php

use App\Models\Bill;
use App\Models\BillLine;
use App\Models\BillOfMaterial;
use App\Models\InventoryAdjustment;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\PosFavorite;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use App\Models\QualityControl;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\Rfq;
use App\Models\RfqLine;
use App\Models\SaleOrder;
use App\Models\SaleOrderLine;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('exposes relations for every product foreign key', function () {
    $product = new Product;

    expect($product->category())->toBeInstanceOf(BelongsTo::class)
        ->and($product->variants())->toBeInstanceOf(HasMany::class)
        ->and($product->billOfMaterials())->toBeInstanceOf(HasMany::class)
        ->and($product->componentBillOfMaterials())->toBeInstanceOf(HasMany::class)
        ->and($product->stockTransfers())->toBeInstanceOf(HasMany::class)
        ->and($product->inventoryAdjustments())->toBeInstanceOf(HasMany::class)
        ->and($product->productionOrders())->toBeInstanceOf(HasMany::class)
        ->and($product->qualityControls())->toBeInstanceOf(HasMany::class)
        ->and($product->quotationLines())->toBeInstanceOf(HasMany::class)
        ->and($product->saleOrderLines())->toBeInstanceOf(HasMany::class)
        ->and($product->invoiceLines())->toBeInstanceOf(HasMany::class)
        ->and($product->purchaseRequestLines())->toBeInstanceOf(HasMany::class)
        ->and($product->rfqLines())->toBeInstanceOf(HasMany::class)
        ->and($product->purchaseOrderLines())->toBeInstanceOf(HasMany::class)
        ->and($product->billLines())->toBeInstanceOf(HasMany::class)
        ->and($product->posSaleItems())->toBeInstanceOf(HasMany::class)
        ->and($product->posFavorites())->toBeInstanceOf(HasMany::class);
});

it('loads inverse records through product relations', function () {
    $product = Product::factory()->create();
    $component = Product::factory()->create();

    Variant::factory()->create(['product_id' => $product->id]);
    BillOfMaterial::factory()->create([
        'product_id' => $product->id,
        'component_product_id' => $component->id,
    ]);
    StockTransfer::factory()->create(['product_id' => $product->id]);
    InventoryAdjustment::factory()->create(['product_id' => $product->id]);
    ProductionOrder::factory()->create(['product_id' => $product->id]);
    QualityControl::factory()->create(['product_id' => $product->id]);

    $line = [
        'product_id' => $product->id,
        'line_number' => 1,
        'description' => $product->name,
        'quantity' => 1,
        'unit_price' => 10,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'line_total' => 10,
    ];

    QuotationLine::query()->create($line + ['quotation_id' => Quotation::factory()->create()->id]);
    SaleOrderLine::query()->create($line + [
        'sale_order_id' => SaleOrder::factory()->create()->id,
        'quantity_invoiced' => 0,
        'quantity_fulfilled' => 0,
    ]);
    InvoiceLine::query()->create($line + ['invoice_id' => Invoice::factory()->create()->id]);
    PurchaseRequestLine::query()->create($line + ['purchase_request_id' => PurchaseRequest::factory()->create()->id]);
    RfqLine::query()->create($line + ['rfq_id' => Rfq::factory()->create()->id]);
    PurchaseOrderLine::query()->create($line + [
        'purchase_order_id' => PurchaseOrder::factory()->create()->id,
        'quantity_billed' => 0,
    ]);
    BillLine::query()->create($line + ['bill_id' => Bill::factory()->create()->id]);

    $sale = PosSale::factory()->create();
    PosSaleItem::query()->create([
        'pos_sale_id' => $sale->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 1,
        'unit_price' => 10,
        'discount_amount' => 0,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'line_total' => 10,
    ]);
    PosFavorite::query()->create([
        'user_id' => User::factory()->create()->id,
        'product_id' => $product->id,
    ]);

    $product->load([
        'variants',
        'billOfMaterials',
        'componentBillOfMaterials',
        'stockTransfers',
        'inventoryAdjustments',
        'productionOrders',
        'qualityControls',
        'quotationLines',
        'saleOrderLines',
        'invoiceLines',
        'purchaseRequestLines',
        'rfqLines',
        'purchaseOrderLines',
        'billLines',
        'posSaleItems',
        'posFavorites',
    ]);

    expect($product->variants)->toHaveCount(1)
        ->and($product->billOfMaterials)->toHaveCount(1)
        ->and($product->componentBillOfMaterials)->toHaveCount(0)
        ->and($component->componentBillOfMaterials)->toHaveCount(1)
        ->and($product->stockTransfers)->toHaveCount(1)
        ->and($product->inventoryAdjustments)->toHaveCount(1)
        ->and($product->productionOrders)->toHaveCount(1)
        ->and($product->qualityControls)->toHaveCount(1)
        ->and($product->quotationLines)->toHaveCount(1)
        ->and($product->saleOrderLines)->toHaveCount(1)
        ->and($product->invoiceLines)->toHaveCount(1)
        ->and($product->purchaseRequestLines)->toHaveCount(1)
        ->and($product->rfqLines)->toHaveCount(1)
        ->and($product->purchaseOrderLines)->toHaveCount(1)
        ->and($product->billLines)->toHaveCount(1)
        ->and($product->posSaleItems)->toHaveCount(1)
        ->and($product->posFavorites)->toHaveCount(1);
});
