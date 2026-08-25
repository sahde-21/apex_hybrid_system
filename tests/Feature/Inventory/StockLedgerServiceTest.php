<?php

use App\Enums\StockMovementType;
use App\Exceptions\Inventory\InactiveProductException;
use App\Exceptions\Inventory\InactiveVariantException;
use App\Exceptions\Inventory\InactiveWarehouseException;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Exceptions\Inventory\InvalidReservationException;
use App\Exceptions\Inventory\InvalidStockIdentityException;
use App\Exceptions\Inventory\InvalidStockQuantityException;
use App\Exceptions\Inventory\StockMovementImmutableException;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Services\Inventory\StockLedgerService;
use App\Support\Inventory\MovementCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function ledger(): StockLedgerService
{
    return app(StockLedgerService::class);
}

function stockCommand(array $overrides = []): MovementCommand
{
    $warehouse = $overrides['warehouse'] ?? Warehouse::factory()->create(['is_active' => true]);
    $product = $overrides['product'] ?? Product::factory()->create([
        'is_active' => true,
        'stock_quantity' => $overrides['product_stock'] ?? 999,
    ]);

    unset($overrides['warehouse'], $overrides['product'], $overrides['product_stock']);

    return MovementCommand::fromArray(array_merge([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 10,
        'reserved_delta' => 0,
        'movement_type' => StockMovementType::Adjustment,
        'idempotency_key' => 'test-'.Str::uuid(),
        'occurred_at' => now(),
    ], $overrides));
}

test('posting creates a stock level for warehouse and product', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 50]);

    $result = ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 10,
        'product_stock' => 50,
    ]));

    expect($result->replayed)->toBeFalse()
        ->and($result->level->warehouse_id)->toBe($warehouse->id)
        ->and($result->level->product_id)->toBe($product->id)
        ->and($result->level->variant_id)->toBeNull()
        ->and($result->level->on_hand)->toBe(10)
        ->and(StockLevel::query()->count())->toBe(1);

    // P0.1 must not mutate compatibility mirrors.
    expect($product->fresh()->stock_quantity)->toBe(50);
});

test('positive movement increases on hand from zero', function () {
    $result = ledger()->post(stockCommand(['quantity' => 10]));

    expect($result->movement->quantity_before)->toBe(0)
        ->and($result->movement->quantity_after)->toBe(10)
        ->and($result->level->on_hand)->toBe(10);
});

test('negative movement decreases on hand', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);

    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 10,
        'idempotency_key' => 'seed-10',
    ]));

    $result = ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => -3,
        'movement_type' => StockMovementType::PosSale,
        'idempotency_key' => 'sale-3',
    ]));

    expect($result->movement->quantity_before)->toBe(10)
        ->and($result->movement->quantity_after)->toBe(7)
        ->and($result->level->on_hand)->toBe(7)
        ->and($result->level->available())->toBe(7);
});

test('insufficient stock is rejected when negative stock is disallowed', function () {
    config(['inventory.allow_negative_stock' => false]);

    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);

    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 2,
        'idempotency_key' => 'seed-2',
    ]));

    expect(fn () => ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => -5,
        'movement_type' => StockMovementType::PosSale,
        'idempotency_key' => 'oversell',
    ])))->toThrow(InsufficientStockException::class);

    expect(StockLevel::query()->first()->on_hand)->toBe(2)
        ->and(StockMovement::query()->count())->toBe(1);
});

test('reservation increases reserved and reduces available', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);

    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 10,
        'idempotency_key' => 'seed-res',
    ]));

    $result = ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 0,
        'reserved_delta' => 4,
        'movement_type' => StockMovementType::SalesReserve,
        'idempotency_key' => 'reserve-4',
    ]));

    expect($result->level->on_hand)->toBe(10)
        ->and($result->level->reserved)->toBe(4)
        ->and($result->level->available())->toBe(6);
});

test('reservation release decreases reserved', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);

    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 10,
        'idempotency_key' => 'seed-rel',
    ]));
    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 0,
        'reserved_delta' => 4,
        'movement_type' => StockMovementType::SalesReserve,
        'idempotency_key' => 'reserve-rel',
    ]));

    $result = ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 0,
        'reserved_delta' => -4,
        'movement_type' => StockMovementType::SalesRelease,
        'idempotency_key' => 'release-rel',
    ]));

    expect($result->level->reserved)->toBe(0)
        ->and($result->level->available())->toBe(10);
});

test('cannot reserve more than available', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);

    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 5,
        'idempotency_key' => 'seed-inv-res',
    ]));

    expect(fn () => ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 0,
        'reserved_delta' => 6,
        'movement_type' => StockMovementType::SalesReserve,
        'idempotency_key' => 'bad-reserve',
    ])))->toThrow(InvalidReservationException::class);
});

test('cannot release more reserved than exists', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);

    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 5,
        'idempotency_key' => 'seed-inv-rel',
    ]));

    expect(fn () => ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 0,
        'reserved_delta' => -1,
        'movement_type' => StockMovementType::SalesRelease,
        'idempotency_key' => 'bad-release',
    ])))->toThrow(InvalidReservationException::class);
});

test('product level and variant level stock identities are isolated', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);
    $variant = Variant::factory()->create([
        'product_id' => $product->id,
        'is_active' => true,
    ]);

    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 10,
        'idempotency_key' => 'product-level',
    ]));

    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'variant_id' => $variant->id,
        'quantity' => 3,
        'idempotency_key' => 'variant-level',
    ]));

    $productLevel = StockLevel::query()
        ->where('warehouse_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->whereNull('variant_id')
        ->firstOrFail();

    $variantLevel = StockLevel::query()
        ->where('warehouse_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->where('variant_id', $variant->id)
        ->firstOrFail();

    expect($productLevel->on_hand)->toBe(10)
        ->and($variantLevel->on_hand)->toBe(3)
        ->and(StockLevel::query()->count())->toBe(2);
});

test('warehouse stock is isolated per warehouse', function () {
    $warehouseA = Warehouse::factory()->create(['is_active' => true]);
    $warehouseB = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);

    ledger()->post(stockCommand([
        'warehouse' => $warehouseA,
        'product' => $product,
        'quantity' => 8,
        'idempotency_key' => 'wh-a',
    ]));

    ledger()->post(stockCommand([
        'warehouse' => $warehouseB,
        'product' => $product,
        'quantity' => 2,
        'idempotency_key' => 'wh-b',
    ]));

    expect(
        StockLevel::query()->where('warehouse_id', $warehouseA->id)->value('on_hand')
    )->toBe(8)->and(
        StockLevel::query()->where('warehouse_id', $warehouseB->id)->value('on_hand')
    )->toBe(2);
});

test('idempotent posting returns the same movement without double applying', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);
    $key = 'idem-'.Str::uuid();

    $first = ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 5,
        'idempotency_key' => $key,
    ]));

    $second = ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 5,
        'idempotency_key' => $key,
    ]));

    expect($second->replayed)->toBeTrue()
        ->and($second->movement->id)->toBe($first->movement->id)
        ->and(StockMovement::query()->count())->toBe(1)
        ->and(StockLevel::query()->first()->on_hand)->toBe(5);
});

test('many sequential posts do not lose quantity updates', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);

    foreach (range(1, 25) as $i) {
        ledger()->post(stockCommand([
            'warehouse' => $warehouse,
            'product' => $product,
            'quantity' => 1,
            'idempotency_key' => "seq-{$i}",
        ]));
    }

    expect(StockLevel::query()->first()->on_hand)->toBe(25)
        ->and(StockMovement::query()->count())->toBe(25);
});

test('posted movements cannot be updated or deleted', function () {
    $result = ledger()->post(stockCommand(['quantity' => 4]));

    expect(fn () => $result->movement->update(['notes' => 'tamper']))
        ->toThrow(StockMovementImmutableException::class);

    expect(fn () => $result->movement->delete())
        ->toThrow(StockMovementImmutableException::class);
});

test('invalid warehouse product and variant references fail safely', function () {
    $product = Product::factory()->create(['is_active' => true]);

    expect(fn () => ledger()->post(MovementCommand::fromArray([
        'warehouse_id' => 999999,
        'product_id' => $product->id,
        'quantity' => 1,
        'movement_type' => StockMovementType::Adjustment->value,
        'idempotency_key' => 'bad-wh',
    ])))->toThrow(InvalidStockIdentityException::class);

    $warehouse = Warehouse::factory()->create(['is_active' => true]);

    expect(fn () => ledger()->post(MovementCommand::fromArray([
        'warehouse_id' => $warehouse->id,
        'product_id' => 999999,
        'quantity' => 1,
        'movement_type' => StockMovementType::Adjustment->value,
        'idempotency_key' => 'bad-product',
    ])))->toThrow(InvalidStockIdentityException::class);

    $otherProduct = Product::factory()->create(['is_active' => true]);
    $variant = Variant::factory()->create([
        'product_id' => $otherProduct->id,
        'is_active' => true,
    ]);

    expect(fn () => ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'idempotency_key' => 'bad-variant-link',
    ])))->toThrow(InvalidStockIdentityException::class);
});

test('failed posting rolls back stock level changes', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);

    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 5,
        'idempotency_key' => 'rb-seed',
    ]));

    expect(fn () => DB::transaction(function () use ($warehouse, $product) {
        ledger()->post(stockCommand([
            'warehouse' => $warehouse,
            'product' => $product,
            'quantity' => 2,
            'idempotency_key' => 'rb-inner',
        ]));

        throw new RuntimeException('force rollback');
    }))->toThrow(RuntimeException::class);

    expect(StockLevel::query()->first()->on_hand)->toBe(5)
        ->and(StockMovement::query()->where('idempotency_key', 'rb-inner')->exists())->toBeFalse();
});

test('zero quantity with zero reserved delta is rejected', function () {
    expect(fn () => ledger()->post(stockCommand([
        'quantity' => 0,
        'reserved_delta' => 0,
        'idempotency_key' => 'zero-move',
    ])))->toThrow(InvalidStockQuantityException::class);
});

test('inactive warehouse and product are rejected', function () {
    $inactiveWarehouse = Warehouse::factory()->create(['is_active' => false]);
    $product = Product::factory()->create(['is_active' => true]);

    expect(fn () => ledger()->post(stockCommand([
        'warehouse' => $inactiveWarehouse,
        'product' => $product,
        'quantity' => 1,
        'idempotency_key' => 'inactive-wh',
    ])))->toThrow(InactiveWarehouseException::class);

    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $inactiveProduct = Product::factory()->create(['is_active' => false]);

    expect(fn () => ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $inactiveProduct,
        'quantity' => 1,
        'idempotency_key' => 'inactive-product',
    ])))->toThrow(InactiveProductException::class);
});

test('inactive variant is rejected', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);
    $variant = Variant::factory()->create([
        'product_id' => $product->id,
        'is_active' => false,
    ]);

    expect(fn () => ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'idempotency_key' => 'inactive-variant',
    ])))->toThrow(InactiveVariantException::class);
});

test('sales fulfillment against reservation reduces on hand and reserved together', function () {
    $warehouse = Warehouse::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);

    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 10,
        'idempotency_key' => 'ff-seed',
    ]));
    ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => 0,
        'reserved_delta' => 4,
        'movement_type' => StockMovementType::SalesReserve,
        'idempotency_key' => 'ff-reserve',
    ]));

    $result = ledger()->post(stockCommand([
        'warehouse' => $warehouse,
        'product' => $product,
        'quantity' => -4,
        'reserved_delta' => -4,
        'movement_type' => StockMovementType::SalesFulfillment,
        'idempotency_key' => 'ff-ship',
    ]));

    expect($result->level->on_hand)->toBe(6)
        ->and($result->level->reserved)->toBe(0)
        ->and($result->level->available())->toBe(6);
});

test('ledger remains disabled by default configuration', function () {
    expect(config('inventory.ledger_enabled'))->toBeFalse()
        ->and(config('inventory.allow_negative_stock'))->toBeFalse();
});
