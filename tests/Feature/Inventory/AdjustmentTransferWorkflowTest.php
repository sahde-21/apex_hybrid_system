<?php

use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryAdjustmentWorkflowService;
use App\Services\Inventory\StockLedgerService;
use App\Services\Inventory\StockTransferWorkflowService;
use App\Support\Inventory\MovementCommand;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = actingAsSuperAdmin();
    $this->warehouseA = Warehouse::factory()->create(['code' => 'ADJ-A', 'is_active' => true]);
    $this->warehouseB = Warehouse::factory()->create(['code' => 'ADJ-B', 'is_active' => true]);
    $this->product = Product::factory()->create([
        'is_active' => true,
        'stock_quantity' => 50,
        'sale_price' => 10,
    ]);
});

function seedOnHand(Warehouse $warehouse, Product $product, int $qty): void
{
    app(StockLedgerService::class)->post(MovementCommand::fromArray([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => $qty,
        'reserved_delta' => 0,
        'movement_type' => StockMovementType::OpeningBalance,
        'idempotency_key' => 'p04-seed-'.Str::uuid(),
        'occurred_at' => now(),
    ]));
}

function adjustments(): InventoryAdjustmentWorkflowService
{
    return app(InventoryAdjustmentWorkflowService::class);
}

function transfers(): StockTransferWorkflowService
{
    return app(StockTransferWorkflowService::class);
}

test('draft adjustment does not mutate stock when ledger enabled', function () {
    config(['inventory.ledger_enabled' => true]);
    seedOnHand($this->warehouseA, $this->product, 10);

    adjustments()->createDraft([
        'reference_number' => 'IA-DRAFT-1',
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouseA->id,
        'adjustment_date' => now()->toDateString(),
        'quantity_change' => -2,
        'reason' => InventoryAdjustmentReason::Damage->value,
    ], $this->user);

    expect((int) StockLevel::query()->where('warehouse_id', $this->warehouseA->id)->value('on_hand'))->toBe(10)
        ->and($this->product->fresh()->stock_quantity)->toBe(10)
        ->and(StockMovement::query()->where('movement_type', StockMovementType::Damage)->count())->toBe(0);
});

test('approval does not mutate stock', function () {
    config(['inventory.ledger_enabled' => true]);
    seedOnHand($this->warehouseA, $this->product, 10);

    $adj = adjustments()->createDraft([
        'reference_number' => 'IA-APR-1',
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouseA->id,
        'adjustment_date' => now()->toDateString(),
        'quantity_change' => 5,
        'reason' => InventoryAdjustmentReason::Found->value,
    ], $this->user);

    adjustments()->approve($adj, $this->user);

    expect($adj->fresh()->status)->toBe(InventoryAdjustmentStatus::Approved)
        ->and((int) StockLevel::query()->where('product_id', $this->product->id)->value('on_hand'))->toBe(10);
});

test('posting creates positive and negative ledger movements and mirrors', function () {
    config(['inventory.ledger_enabled' => true]);
    seedOnHand($this->warehouseA, $this->product, 10);

    $pos = adjustments()->createDraft([
        'reference_number' => 'IA-POS',
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouseA->id,
        'adjustment_date' => now()->toDateString(),
        'quantity_change' => 3,
        'reason' => InventoryAdjustmentReason::Correction->value,
    ], $this->user);
    adjustments()->approve($pos, $this->user);
    adjustments()->post($pos, $this->user);

    $neg = adjustments()->createDraft([
        'reference_number' => 'IA-NEG',
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouseA->id,
        'adjustment_date' => now()->toDateString(),
        'quantity_change' => -2,
        'reason' => InventoryAdjustmentReason::Loss->value,
    ], $this->user);
    adjustments()->approve($neg, $this->user);
    adjustments()->post($neg, $this->user);

    expect((int) StockLevel::query()->where('warehouse_id', $this->warehouseA->id)->value('on_hand'))->toBe(11)
        ->and($this->product->fresh()->stock_quantity)->toBe(11)
        ->and(StockMovement::query()->where('idempotency_key', 'adjustment:'.$pos->id.':post')->exists())->toBeTrue()
        ->and(StockMovement::query()->where('movement_type', StockMovementType::Loss)->value('quantity'))->toBe(-2);
});

test('zero quantity rejected and null warehouse cannot post', function () {
    expect(fn () => adjustments()->createDraft([
        'reference_number' => 'IA-ZERO',
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouseA->id,
        'adjustment_date' => now()->toDateString(),
        'quantity_change' => 0,
        'reason' => InventoryAdjustmentReason::Other->value,
    ], $this->user))->toThrow(InvalidArgumentException::class);

    $adj = InventoryAdjustment::factory()->create([
        'product_id' => $this->product->id,
        'warehouse_id' => null,
        'quantity_change' => 1,
        'reason' => InventoryAdjustmentReason::Correction->value,
        'status' => InventoryAdjustmentStatus::Draft,
    ]);

    expect(fn () => adjustments()->approve($adj, $this->user))->toThrow(InvalidArgumentException::class);
});

test('insufficient stock on negative post rolls back status', function () {
    config(['inventory.ledger_enabled' => true]);
    seedOnHand($this->warehouseA, $this->product, 1);

    $adj = adjustments()->createDraft([
        'reference_number' => 'IA-INS',
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouseA->id,
        'adjustment_date' => now()->toDateString(),
        'quantity_change' => -5,
        'reason' => InventoryAdjustmentReason::Damage->value,
    ], $this->user);
    adjustments()->approve($adj, $this->user);

    expect(fn () => adjustments()->post($adj, $this->user))->toThrow(InvalidArgumentException::class);
    expect($adj->fresh()->status)->toBe(InventoryAdjustmentStatus::Approved)
        ->and((int) StockLevel::query()->value('on_hand'))->toBe(1)
        ->and(StockMovement::query()->where('movement_type', StockMovementType::Damage)->count())->toBe(0);
});

test('duplicate posting is idempotent for ledger key', function () {
    config(['inventory.ledger_enabled' => true]);
    seedOnHand($this->warehouseA, $this->product, 10);

    $adj = adjustments()->createDraft([
        'reference_number' => 'IA-IDEM',
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouseA->id,
        'adjustment_date' => now()->toDateString(),
        'quantity_change' => -1,
        'reason' => InventoryAdjustmentReason::Expiry->value,
    ], $this->user);
    adjustments()->approve($adj, $this->user);
    adjustments()->post($adj, $this->user);

    $replay = app(StockLedgerService::class)->post(MovementCommand::fromArray([
        'warehouse_id' => $this->warehouseA->id,
        'product_id' => $this->product->id,
        'quantity' => -1,
        'movement_type' => StockMovementType::Expiry,
        'idempotency_key' => 'adjustment:'.$adj->id.':post',
        'occurred_at' => now(),
        'reason_code' => 'expiry',
    ]));

    expect($replay->replayed)->toBeTrue()
        ->and((int) StockLevel::query()->value('on_hand'))->toBe(9)
        ->and(fn () => adjustments()->post($adj->fresh(), $this->user))->toThrow(InvalidArgumentException::class);
});

test('posted adjustment cannot be edited or cancelled', function () {
    config(['inventory.ledger_enabled' => false]);

    $adj = adjustments()->createDraft([
        'reference_number' => 'IA-IMM',
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouseA->id,
        'adjustment_date' => now()->toDateString(),
        'quantity_change' => 2,
        'reason' => InventoryAdjustmentReason::Correction->value,
    ], $this->user);
    adjustments()->approve($adj, $this->user);
    adjustments()->post($adj, $this->user);

    expect(fn () => adjustments()->updateDraft($adj->fresh(), ['quantity_change' => 9], $this->user))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => adjustments()->cancel($adj->fresh(), $this->user))
        ->toThrow(InvalidArgumentException::class)
        ->and(StockMovement::query()->count())->toBe(0)
        ->and($this->product->fresh()->stock_quantity)->toBe(50);
});

test('flag off post and ship never mutate legacy stock or ledger', function () {
    expect(config('inventory.ledger_enabled'))->toBeFalse();

    $adj = adjustments()->createDraft([
        'reference_number' => 'IA-OFF',
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouseA->id,
        'adjustment_date' => now()->toDateString(),
        'quantity_change' => -4,
        'reason' => InventoryAdjustmentReason::Loss->value,
    ], $this->user);
    adjustments()->approve($adj, $this->user);
    adjustments()->post($adj, $this->user);

    $transfer = transfers()->createDraft([
        'reference_number' => 'TR-OFF',
        'product_id' => $this->product->id,
        'from_warehouse_id' => $this->warehouseA->id,
        'to_warehouse_id' => $this->warehouseB->id,
        'quantity' => 3,
        'transfer_date' => now()->toDateString(),
    ], $this->user);
    transfers()->approve($transfer, $this->user);
    transfers()->ship($transfer, $this->user);
    transfers()->receive($transfer, $this->user);

    expect($this->product->fresh()->stock_quantity)->toBe(50)
        ->and(StockLevel::query()->count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0)
        ->and($adj->fresh()->status)->toBe(InventoryAdjustmentStatus::Posted)
        ->and($transfer->fresh()->status)->toBe(StockTransferStatus::Completed);
});

test('transfer draft and approve do not mutate stock; ship decreases source only', function () {
    config(['inventory.ledger_enabled' => true]);
    seedOnHand($this->warehouseA, $this->product, 100);
    seedOnHand($this->warehouseB, $this->product, 20);

    $transfer = transfers()->createDraft([
        'reference_number' => 'TR-FLOW',
        'product_id' => $this->product->id,
        'from_warehouse_id' => $this->warehouseA->id,
        'to_warehouse_id' => $this->warehouseB->id,
        'quantity' => 30,
        'transfer_date' => now()->toDateString(),
    ], $this->user);
    transfers()->approve($transfer, $this->user);

    expect((int) StockLevel::query()->where('warehouse_id', $this->warehouseA->id)->value('on_hand'))->toBe(100)
        ->and((int) StockLevel::query()->where('warehouse_id', $this->warehouseB->id)->value('on_hand'))->toBe(20);

    transfers()->ship($transfer, $this->user);

    expect($transfer->fresh()->status)->toBe(StockTransferStatus::InTransit)
        ->and((int) StockLevel::query()->where('warehouse_id', $this->warehouseA->id)->value('on_hand'))->toBe(70)
        ->and((int) StockLevel::query()->where('warehouse_id', $this->warehouseB->id)->value('on_hand'))->toBe(20)
        ->and($this->product->fresh()->stock_quantity)->toBe(90);

    transfers()->receive($transfer->fresh(), $this->user);

    expect($transfer->fresh()->status)->toBe(StockTransferStatus::Completed)
        ->and((int) StockLevel::query()->where('warehouse_id', $this->warehouseA->id)->value('on_hand'))->toBe(70)
        ->and((int) StockLevel::query()->where('warehouse_id', $this->warehouseB->id)->value('on_hand'))->toBe(50)
        ->and($this->product->fresh()->stock_quantity)->toBe(120);
});

test('receive before ship and cancel after ship are rejected', function () {
    config(['inventory.ledger_enabled' => true]);
    seedOnHand($this->warehouseA, $this->product, 10);

    $transfer = transfers()->createDraft([
        'reference_number' => 'TR-RULES',
        'product_id' => $this->product->id,
        'from_warehouse_id' => $this->warehouseA->id,
        'to_warehouse_id' => $this->warehouseB->id,
        'quantity' => 2,
        'transfer_date' => now()->toDateString(),
    ], $this->user);
    transfers()->approve($transfer, $this->user);

    expect(fn () => transfers()->receive($transfer, $this->user))->toThrow(InvalidArgumentException::class);

    transfers()->ship($transfer, $this->user);

    expect(fn () => transfers()->cancel($transfer->fresh(), $this->user))->toThrow(InvalidArgumentException::class);
});

test('source equals destination and insufficient source stock are rejected', function () {
    config(['inventory.ledger_enabled' => true]);
    seedOnHand($this->warehouseA, $this->product, 1);

    expect(fn () => transfers()->createDraft([
        'reference_number' => 'TR-SAME',
        'product_id' => $this->product->id,
        'from_warehouse_id' => $this->warehouseA->id,
        'to_warehouse_id' => $this->warehouseA->id,
        'quantity' => 1,
        'transfer_date' => now()->toDateString(),
    ], $this->user))->toThrow(InvalidArgumentException::class);

    $transfer = transfers()->createDraft([
        'reference_number' => 'TR-LOW',
        'product_id' => $this->product->id,
        'from_warehouse_id' => $this->warehouseA->id,
        'to_warehouse_id' => $this->warehouseB->id,
        'quantity' => 5,
        'transfer_date' => now()->toDateString(),
    ], $this->user);
    transfers()->approve($transfer, $this->user);

    expect(fn () => transfers()->ship($transfer, $this->user))->toThrow(InvalidArgumentException::class)
        ->and($transfer->fresh()->status)->toBe(StockTransferStatus::Pending);
});

test('duplicate ship and receive replay ledger keys without double mutation', function () {
    config(['inventory.ledger_enabled' => true]);
    seedOnHand($this->warehouseA, $this->product, 20);

    $transfer = transfers()->createDraft([
        'reference_number' => 'TR-IDEM',
        'product_id' => $this->product->id,
        'from_warehouse_id' => $this->warehouseA->id,
        'to_warehouse_id' => $this->warehouseB->id,
        'quantity' => 4,
        'transfer_date' => now()->toDateString(),
    ], $this->user);
    transfers()->approve($transfer, $this->user);
    transfers()->ship($transfer, $this->user);

    $shipReplay = app(StockLedgerService::class)->post(MovementCommand::fromArray([
        'warehouse_id' => $this->warehouseA->id,
        'product_id' => $this->product->id,
        'quantity' => -4,
        'movement_type' => StockMovementType::TransferShip,
        'idempotency_key' => 'transfer:'.$transfer->id.':ship',
        'occurred_at' => now(),
    ]));

    transfers()->receive($transfer->fresh(), $this->user);

    $receiveReplay = app(StockLedgerService::class)->post(MovementCommand::fromArray([
        'warehouse_id' => $this->warehouseB->id,
        'product_id' => $this->product->id,
        'quantity' => 4,
        'movement_type' => StockMovementType::TransferReceive,
        'idempotency_key' => 'transfer:'.$transfer->id.':receive',
        'occurred_at' => now(),
    ]));

    expect($shipReplay->replayed)->toBeTrue()
        ->and($receiveReplay->replayed)->toBeTrue()
        ->and((int) StockLevel::query()->where('warehouse_id', $this->warehouseA->id)->value('on_hand'))->toBe(16)
        ->and((int) StockLevel::query()->where('warehouse_id', $this->warehouseB->id)->value('on_hand'))->toBe(4)
        ->and(fn () => transfers()->ship($transfer->fresh(), $this->user))->toThrow(InvalidArgumentException::class)
        ->and(fn () => transfers()->receive($transfer->fresh(), $this->user))->toThrow(InvalidArgumentException::class);
});

test('inventory ledger_enabled remains false by default after P0.4 suite', function () {
    expect(config('inventory.ledger_enabled'))->toBeFalse();
});
