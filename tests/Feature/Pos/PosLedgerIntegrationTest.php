<?php

use App\Enums\PosPaymentMethod;
use App\Enums\PosSaleStatus;
use App\Enums\PosShiftStatus;
use App\Enums\StockMovementType;
use App\Models\PosRegister;
use App\Models\PosSale;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\TaxRate;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Services\Inventory\PosLedgerPreflightService;
use App\Services\Inventory\StockLedgerService;
use App\Services\Pos\PosCatalogService;
use App\Services\Pos\PosCheckoutService;
use App\Services\Pos\PosRefundService;
use App\Services\Pos\PosShiftService;
use App\Support\Inventory\MovementCommand;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = actingAsSuperAdmin();
    TaxRate::query()->create([
        'name' => 'VAT',
        'code' => 'VAT0',
        'rate' => 0,
        'is_active' => true,
    ]);

    $this->warehouseA = Warehouse::factory()->create([
        'code' => 'WH-A',
        'name' => 'Warehouse A',
        'is_active' => true,
    ]);
    $this->warehouseB = Warehouse::factory()->create([
        'code' => 'WH-B',
        'name' => 'Warehouse B',
        'is_active' => true,
    ]);

    $this->registerA = PosRegister::factory()->create([
        'code' => 'REG-A',
        'warehouse_id' => $this->warehouseA->id,
        'is_active' => true,
    ]);
    $this->registerB = PosRegister::factory()->create([
        'code' => 'REG-B',
        'warehouse_id' => $this->warehouseB->id,
        'is_active' => true,
    ]);
});

function seedLedgerStock(Warehouse $warehouse, Product $product, int $qty, ?Variant $variant = null): void
{
    app(StockLedgerService::class)->post(MovementCommand::fromArray([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'variant_id' => $variant?->id,
        'quantity' => $qty,
        'reserved_delta' => 0,
        'movement_type' => StockMovementType::OpeningBalance,
        'idempotency_key' => 'seed-'.Str::uuid(),
        'occurred_at' => now(),
        'allow_inactive' => true,
    ]));
}

function posCheckout(PosRegister $register, $user, array $items, ?array $payments = null): PosSale
{
    $shifts = app(PosShiftService::class);

    $open = $register->openShift();
    if ($open && $open->user_id === $user->id) {
        $shifts->close($open, (float) $open->opening_float);
    } elseif ($open) {
        $open->update(['status' => PosShiftStatus::Closed, 'closed_at' => now()]);
    }

    $userOpen = PosShift::query()
        ->where('user_id', $user->id)
        ->where('status', PosShiftStatus::Open)
        ->first();
    if ($userOpen) {
        $shifts->close($userOpen, (float) $userOpen->opening_float);
    }

    $shift = $shifts->open($register, $user, 0);
    $total = collect($items)->sum(fn ($i) => (float) $i['unit_price'] * (int) $i['quantity']);

    return app(PosCheckoutService::class)->checkout(
        shift: $shift,
        user: $user,
        items: $items,
        payments: $payments ?? [['method' => PosPaymentMethod::Cash->value, 'amount' => $total]],
    );
}

test('flag off preserves existing POS stock behavior and ignores stock_levels', function () {
    expect(config('inventory.ledger_enabled'))->toBeFalse();

    $product = Product::factory()->create([
        'sale_price' => 10,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    seedLedgerStock($this->warehouseA, $product, 99);

    $sale = posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 2,
        'unit_price' => 10,
        'tax_rate' => 0,
    ]]);

    expect($sale->status)->toBe(PosSaleStatus::Completed)
        ->and($product->fresh()->stock_quantity)->toBe(8)
        ->and((int) StockLevel::query()->where('warehouse_id', $this->warehouseA->id)->where('product_id', $product->id)->value('on_hand'))->toBe(99)
        ->and(StockMovement::query()->where('movement_type', StockMovementType::PosSale)->count())->toBe(0);
});

test('flag on simple product sale posts ledger and updates mirror', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create([
        'sale_price' => 25,
        'stock_quantity' => 0,
        'is_active' => true,
    ]);
    seedLedgerStock($this->warehouseA, $product, 10);

    expect($product->fresh()->stock_quantity)->toBe(10);

    $sale = posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => 25,
        'tax_rate' => 0,
    ]]);

    $level = StockLevel::query()
        ->where('warehouse_id', $this->warehouseA->id)
        ->where('product_id', $product->id)
        ->whereNull('variant_id')
        ->first();

    $movement = StockMovement::query()
        ->where('movement_type', StockMovementType::PosSale)
        ->where('reference_id', $sale->id)
        ->first();

    expect($level->on_hand)->toBe(7)
        ->and($product->fresh()->stock_quantity)->toBe(7)
        ->and($movement)->not->toBeNull()
        ->and($movement->quantity)->toBe(-3)
        ->and($movement->warehouse_id)->toBe($this->warehouseA->id)
        ->and($movement->idempotency_key)->toBe('pos_sale:'.$sale->id.':line:'.$sale->items->first()->id);
});

test('flag on variant sale uses variant stock level', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 0]);
    $variant = Variant::factory()->create([
        'product_id' => $product->id,
        'sale_price' => 15,
        'stock_quantity' => 0,
        'is_active' => true,
    ]);
    seedLedgerStock($this->warehouseA, $product, 8, $variant);

    $sale = posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price' => 15,
        'tax_rate' => 0,
    ]]);

    expect((int) StockLevel::query()->where('variant_id', $variant->id)->value('on_hand'))->toBe(6)
        ->and($variant->fresh()->stock_quantity)->toBe(6)
        ->and($product->fresh()->stock_quantity)->toBe(0)
        ->and($sale->items->first()->variant_id)->toBe($variant->id);
});

test('correct warehouse is used from register', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['sale_price' => 5, 'stock_quantity' => 0, 'is_active' => true]);
    seedLedgerStock($this->warehouseA, $product, 5);
    seedLedgerStock($this->warehouseB, $product, 20);

    posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 5,
        'tax_rate' => 0,
    ]]);

    expect((int) StockLevel::query()->where('warehouse_id', $this->warehouseA->id)->where('product_id', $product->id)->value('on_hand'))->toBe(4)
        ->and((int) StockLevel::query()->where('warehouse_id', $this->warehouseB->id)->where('product_id', $product->id)->value('on_hand'))->toBe(20)
        ->and($product->fresh()->stock_quantity)->toBe(24);
});

test('register without warehouse fails clearly when ledger enabled', function () {
    config(['inventory.ledger_enabled' => true]);

    $register = PosRegister::factory()->create([
        'code' => 'NO-WH',
        'warehouse_id' => null,
        'is_active' => true,
    ]);
    $product = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 5, 'is_active' => true]);

    expect(fn () => posCheckout($register, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 10,
        'tax_rate' => 0,
    ]]))->toThrow(InvalidArgumentException::class, 'no warehouse');
});

test('inactive warehouse fails when ledger enabled', function () {
    config(['inventory.ledger_enabled' => true]);

    $this->warehouseA->update(['is_active' => false]);
    $product = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 0, 'is_active' => true]);
    // Seed while temporarily active? Level may already exist from factory path — post with allow_inactive.
    app(StockLedgerService::class)->post(MovementCommand::fromArray([
        'warehouse_id' => $this->warehouseA->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 5,
        'reserved_delta' => 0,
        'movement_type' => StockMovementType::OpeningBalance,
        'idempotency_key' => 'seed-inactive-'.Str::uuid(),
        'occurred_at' => now(),
        'allow_inactive' => true,
    ]));

    expect(fn () => posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 10,
        'tax_rate' => 0,
    ]]))->toThrow(InvalidArgumentException::class, 'inactive');
});

test('warehouse isolation prevents selling warehouse B stock from warehouse A register', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 0, 'is_active' => true]);
    seedLedgerStock($this->warehouseB, $product, 50);

    expect(fn () => posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 10,
        'tax_rate' => 0,
    ]]))->toThrow(InvalidArgumentException::class);

    expect(PosSale::query()->count())->toBe(0)
        ->and((int) StockLevel::query()->where('warehouse_id', $this->warehouseB->id)->value('on_hand'))->toBe(50)
        ->and($product->fresh()->stock_quantity)->toBe(50);
});

test('insufficient stock on second line rolls back complete sale', function () {
    config(['inventory.ledger_enabled' => true]);

    $p1 = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 0, 'is_active' => true, 'name' => 'P1']);
    $p2 = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 0, 'is_active' => true, 'name' => 'P2']);
    seedLedgerStock($this->warehouseA, $p1, 5);
    seedLedgerStock($this->warehouseA, $p2, 1);

    expect(fn () => posCheckout($this->registerA, $this->user, [
        ['product_id' => $p1->id, 'quantity' => 1, 'unit_price' => 10, 'tax_rate' => 0],
        ['product_id' => $p2->id, 'quantity' => 2, 'unit_price' => 10, 'tax_rate' => 0],
    ]))->toThrow(InvalidArgumentException::class);

    expect(PosSale::query()->count())->toBe(0)
        ->and((int) StockLevel::query()->where('product_id', $p1->id)->value('on_hand'))->toBe(5)
        ->and((int) StockLevel::query()->where('product_id', $p2->id)->value('on_hand'))->toBe(1)
        ->and(StockMovement::query()->where('movement_type', StockMovementType::PosSale)->count())->toBe(0);
});

test('multi-line sale posts deterministic ledger movements atomically', function () {
    config(['inventory.ledger_enabled' => true]);

    $p1 = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 0, 'is_active' => true]);
    $p2 = Product::factory()->create(['sale_price' => 20, 'stock_quantity' => 0, 'is_active' => true]);
    seedLedgerStock($this->warehouseA, $p1, 5);
    seedLedgerStock($this->warehouseA, $p2, 5);

    $sale = posCheckout($this->registerA, $this->user, [
        ['product_id' => $p2->id, 'quantity' => 1, 'unit_price' => 20, 'tax_rate' => 0],
        ['product_id' => $p1->id, 'quantity' => 2, 'unit_price' => 10, 'tax_rate' => 0],
    ]);

    $keys = StockMovement::query()
        ->where('movement_type', StockMovementType::PosSale)
        ->orderBy('idempotency_key')
        ->pluck('idempotency_key')
        ->all();

    expect($sale->items)->toHaveCount(2)
        ->and($keys)->toHaveCount(2)
        ->and((int) StockLevel::query()->where('product_id', $p1->id)->value('on_hand'))->toBe(3)
        ->and((int) StockLevel::query()->where('product_id', $p2->id)->value('on_hand'))->toBe(4);
});

test('payment mismatch fails before stock mutation', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 0, 'is_active' => true]);
    seedLedgerStock($this->warehouseA, $product, 10);

    $shift = app(PosShiftService::class)->open($this->registerA, $this->user, 0);

    expect(fn () => app(PosCheckoutService::class)->checkout(
        shift: $shift,
        user: $this->user,
        items: [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10, 'tax_rate' => 0]],
        payments: [['method' => 'cash', 'amount' => 1]],
    ))->toThrow(InvalidArgumentException::class);

    expect(PosSale::query()->count())->toBe(0)
        ->and((int) StockLevel::query()->where('product_id', $product->id)->value('on_hand'))->toBe(10);
});

test('refund restores ledger quantity and mirror', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['sale_price' => 50, 'stock_quantity' => 0, 'is_active' => true]);
    seedLedgerStock($this->warehouseA, $product, 10);

    $sale = posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 50,
        'tax_rate' => 0,
    ]]);

    $refund = app(PosRefundService::class)->refund($sale, $this->user);

    expect($refund->is_return)->toBeTrue()
        ->and((int) StockLevel::query()->where('product_id', $product->id)->where('warehouse_id', $this->warehouseA->id)->value('on_hand'))->toBe(10)
        ->and($product->fresh()->stock_quantity)->toBe(10)
        ->and(StockMovement::query()->where('movement_type', StockMovementType::PosRefund)->count())->toBe(1)
        ->and($sale->fresh()->status)->toBe(PosSaleStatus::Refunded);
});

test('partial refund restores only refunded quantity', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['sale_price' => 20, 'stock_quantity' => 0, 'is_active' => true]);
    seedLedgerStock($this->warehouseA, $product, 10);

    $sale = posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 4,
        'unit_price' => 20,
        'tax_rate' => 0,
    ]]);

    $itemId = $sale->items->first()->id;

    app(PosRefundService::class)->refund($sale, $this->user, [
        ['pos_sale_item_id' => $itemId, 'quantity' => 1],
    ]);

    expect((int) StockLevel::query()->where('product_id', $product->id)->value('on_hand'))->toBe(7)
        ->and($product->fresh()->stock_quantity)->toBe(7)
        ->and($sale->fresh()->status)->toBe(PosSaleStatus::PartiallyRefunded);
});

test('duplicate full refund is rejected', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 0, 'is_active' => true]);
    seedLedgerStock($this->warehouseA, $product, 5);

    $sale = posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 10,
        'tax_rate' => 0,
    ]]);

    app(PosRefundService::class)->refund($sale, $this->user);

    expect(fn () => app(PosRefundService::class)->refund($sale->fresh(), $this->user))
        ->toThrow(InvalidArgumentException::class);

    expect((int) StockLevel::query()->where('product_id', $product->id)->value('on_hand'))->toBe(5)
        ->and(StockMovement::query()->where('movement_type', StockMovementType::PosRefund)->count())->toBe(1);
});

test('ledger movement idempotency key replays without double deduct', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 0, 'is_active' => true]);
    seedLedgerStock($this->warehouseA, $product, 10);

    $sale = posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 10,
        'tax_rate' => 0,
    ]]);

    $item = $sale->items->first();
    $key = sprintf('pos_sale:%d:line:%d', $sale->id, $item->id);

    $replay = app(StockLedgerService::class)->post(MovementCommand::fromArray([
        'warehouse_id' => $this->warehouseA->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => -2,
        'reserved_delta' => 0,
        'movement_type' => StockMovementType::PosSale,
        'idempotency_key' => $key,
        'occurred_at' => now(),
        'reference_type' => PosSale::class,
        'reference_id' => $sale->id,
        'reference_line_id' => $item->id,
    ]));

    expect($replay->replayed)->toBeTrue()
        ->and((int) StockLevel::query()->where('product_id', $product->id)->value('on_hand'))->toBe(8)
        ->and(StockMovement::query()->where('idempotency_key', $key)->count())->toBe(1);
});

test('legacy product mirror equals sum of stock_levels across warehouses', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['stock_quantity' => 0, 'is_active' => true, 'sale_price' => 1]);
    seedLedgerStock($this->warehouseA, $product, 3);
    seedLedgerStock($this->warehouseB, $product, 7);

    expect($product->fresh()->stock_quantity)->toBe(10);
});

test('legacy variant mirror equals sum of stock_levels', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['stock_quantity' => 0, 'is_active' => true]);
    $variant = Variant::factory()->create([
        'product_id' => $product->id,
        'stock_quantity' => 0,
        'is_active' => true,
        'sale_price' => 1,
    ]);
    seedLedgerStock($this->warehouseA, $product, 4, $variant);
    seedLedgerStock($this->warehouseB, $product, 6, $variant);

    expect($variant->fresh()->stock_quantity)->toBe(10)
        ->and($product->fresh()->stock_quantity)->toBe(0);
});

test('pos catalog reads warehouse-specific available quantity', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create([
        'name' => 'Catalog Item',
        'sale_price' => 10,
        'stock_quantity' => 999,
        'is_active' => true,
        'sku' => 'CAT-1',
        'barcode' => 'CAT-1',
    ]);
    seedLedgerStock($this->warehouseA, $product, 4);
    seedLedgerStock($this->warehouseB, $product, 40);

    $catalog = app(PosCatalogService::class);
    $fromA = $catalog->findByScan('CAT-1', $this->warehouseA->id);
    $fromB = $catalog->findByScan('CAT-1', $this->warehouseB->id);
    $withoutWarehouse = $catalog->findByScan('CAT-1', null);

    config(['inventory.ledger_enabled' => false]);
    $flagOff = $catalog->findByScan('CAT-1');

    expect($fromA['stock_quantity'])->toBe(4)
        ->and($fromB['stock_quantity'])->toBe(40)
        ->and($withoutWarehouse['stock_quantity'])->toBe(44)
        ->and($flagOff['stock_quantity'])->toBe(44)
        ->and($product->fresh()->stock_quantity)->toBe(44);
});

test('reserved quantity reduces available quantity in catalog', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create([
        'sale_price' => 10,
        'stock_quantity' => 0,
        'is_active' => true,
        'sku' => 'RSV-1',
        'barcode' => 'RSV-1',
    ]);
    seedLedgerStock($this->warehouseA, $product, 10);

    app(StockLedgerService::class)->post(MovementCommand::fromArray([
        'warehouse_id' => $this->warehouseA->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 0,
        'reserved_delta' => 3,
        'movement_type' => StockMovementType::SalesReserve,
        'idempotency_key' => 'reserve-'.Str::uuid(),
        'occurred_at' => now(),
    ]));

    $mapped = app(PosCatalogService::class)->findByScan('RSV-1', $this->warehouseA->id);

    expect($mapped['stock_quantity'])->toBe(7)
        ->and((int) StockLevel::query()->where('product_id', $product->id)->value('on_hand'))->toBe(10)
        ->and((int) StockLevel::query()->where('product_id', $product->id)->value('reserved'))->toBe(3);
});

test('multiple registers can use the same warehouse', function () {
    config(['inventory.ledger_enabled' => true]);

    $registerA2 = PosRegister::factory()->create([
        'code' => 'REG-A2',
        'warehouse_id' => $this->warehouseA->id,
        'is_active' => true,
    ]);

    $product = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 0, 'is_active' => true]);
    seedLedgerStock($this->warehouseA, $product, 10);

    posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 10,
        'tax_rate' => 0,
    ]]);

    posCheckout($registerA2, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => 10,
        'tax_rate' => 0,
    ]]);

    expect((int) StockLevel::query()->where('warehouse_id', $this->warehouseA->id)->where('product_id', $product->id)->value('on_hand'))->toBe(5)
        ->and($product->fresh()->stock_quantity)->toBe(5);
});

test('flag on does not leave legacy columns ahead of ledger after sale', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 100, 'is_active' => true]);
    seedLedgerStock($this->warehouseA, $product, 10);
    // Mirror sync sets legacy to 10 (SUM), not 100.
    expect($product->fresh()->stock_quantity)->toBe(10);

    posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 10,
        'tax_rate' => 0,
    ]]);

    $level = (int) StockLevel::query()->where('product_id', $product->id)->sum('on_hand');

    expect($product->fresh()->stock_quantity)->toBe($level)
        ->and($level)->toBe(8)
        ->and(StockMovement::query()->where('movement_type', StockMovementType::PosSale)->exists())->toBeTrue();
});

test('flag on then off uses synchronized mirrors safely for legacy path', function () {
    config(['inventory.ledger_enabled' => true]);

    $product = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 0, 'is_active' => true]);
    seedLedgerStock($this->warehouseA, $product, 10);

    posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 10,
        'tax_rate' => 0,
    ]]);

    expect($product->fresh()->stock_quantity)->toBe(8);

    config(['inventory.ledger_enabled' => false]);

    posCheckout($this->registerA, $this->user, [[
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 10,
        'tax_rate' => 0,
    ]]);

    expect($product->fresh()->stock_quantity)->toBe(7)
        ->and((int) StockLevel::query()->where('product_id', $product->id)->value('on_hand'))->toBe(8);
});

test('preflight detects active registers without warehouse and does not enable flag', function () {
    PosRegister::factory()->create([
        'code' => 'MISSING-WH',
        'warehouse_id' => null,
        'is_active' => true,
    ]);

    $result = app(PosLedgerPreflightService::class)->check();

    expect($result->ledgerEnabled)->toBeFalse()
        ->and(config('inventory.ledger_enabled'))->toBeFalse()
        ->and($result->registersWithoutWarehouse)->toContain('MISSING-WH')
        ->and($result->passed)->toBeFalse();
});

test('inventory.ledger_enabled remains false by default after P0.3 suite', function () {
    expect(config('inventory.ledger_enabled'))->toBeFalse();
});
