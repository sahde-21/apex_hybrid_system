<?php

use App\Models\FloorPlan;
use App\Models\InventoryAdjustment;
use App\Models\PosRegister;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('exposes has-many relations for every warehouse foreign key', function () {
    $warehouse = new Warehouse;

    expect($warehouse->purchaseOrders())->toBeInstanceOf(HasMany::class)
        ->and($warehouse->saleOrders())->toBeInstanceOf(HasMany::class)
        ->and($warehouse->productionOrders())->toBeInstanceOf(HasMany::class)
        ->and($warehouse->inventoryAdjustments())->toBeInstanceOf(HasMany::class)
        ->and($warehouse->floorPlans())->toBeInstanceOf(HasMany::class)
        ->and($warehouse->posRegisters())->toBeInstanceOf(HasMany::class)
        ->and($warehouse->outgoingStockTransfers())->toBeInstanceOf(HasMany::class)
        ->and($warehouse->incomingStockTransfers())->toBeInstanceOf(HasMany::class);
});

it('loads inverse records through warehouse relations', function () {
    $origin = Warehouse::factory()->create();
    $destination = Warehouse::factory()->create();

    PurchaseOrder::factory()->create(['warehouse_id' => $origin->id]);
    SaleOrder::factory()->create(['warehouse_id' => $origin->id]);
    ProductionOrder::factory()->create(['warehouse_id' => $origin->id]);
    InventoryAdjustment::factory()->create(['warehouse_id' => $origin->id]);
    FloorPlan::factory()->create(['warehouse_id' => $origin->id]);
    PosRegister::factory()->create(['warehouse_id' => $origin->id]);
    StockTransfer::factory()->create([
        'from_warehouse_id' => $origin->id,
        'to_warehouse_id' => $destination->id,
    ]);

    $origin->load([
        'purchaseOrders',
        'saleOrders',
        'productionOrders',
        'inventoryAdjustments',
        'floorPlans',
        'posRegisters',
        'outgoingStockTransfers',
        'incomingStockTransfers',
    ]);

    expect($origin->purchaseOrders)->toHaveCount(1)
        ->and($origin->saleOrders)->toHaveCount(1)
        ->and($origin->productionOrders)->toHaveCount(1)
        ->and($origin->inventoryAdjustments)->toHaveCount(1)
        ->and($origin->floorPlans)->toHaveCount(1)
        ->and($origin->posRegisters)->toHaveCount(1)
        ->and($origin->outgoingStockTransfers)->toHaveCount(1)
        ->and($origin->incomingStockTransfers)->toHaveCount(0)
        ->and($destination->incomingStockTransfers)->toHaveCount(1)
        ->and($destination->outgoingStockTransfers)->toHaveCount(0);
});
