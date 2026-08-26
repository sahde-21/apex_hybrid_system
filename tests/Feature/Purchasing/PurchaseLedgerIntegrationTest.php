<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Inventory\PurchaseLedgerPreflightService;
use App\Services\Purchasing\PurchaseOrderWorkflowService;
use App\Services\Purchasing\PurchaseReceiptWorkflowService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    test()->seed(RolePermissionSeeder::class);
    $this->user = actingAsSuperAdmin();
    $this->warehouse = Warehouse::factory()->create([
        'code' => 'WH-P05',
        'name' => 'P05 Warehouse',
        'is_active' => true,
    ]);
    $this->vendor = Contact::factory()->supplier()->create();
    $this->product = Product::factory()->create([
        'is_active' => true,
        'stock_quantity' => 100,
        'purchase_price' => 10,
        'sale_price' => 15,
    ]);
});

function p05Orders(): PurchaseOrderWorkflowService
{
    return app(PurchaseOrderWorkflowService::class);
}

function p05Receipts(): PurchaseReceiptWorkflowService
{
    return app(PurchaseReceiptWorkflowService::class);
}

function p05ConfirmedOrder($user, Contact $vendor, Warehouse $warehouse, Product $product, int $qty = 10): PurchaseOrder
{
    $order = p05Orders()->create($user, [
        'reference_number' => 'PO-P05-'.Str::upper(Str::random(6)),
        'contact_id' => $vendor->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [[
        'product_id' => $product->id,
        'description' => $product->name,
        'quantity' => $qty,
        'unit_price' => 10,
        'discount_amount' => 0,
        'tax_amount' => 0,
    ]]);

    return p05Orders()->confirm($order, $user);
}

test('flag off receive updates counters and status without ledger or legacy stock mutation', function () {
    expect(config('inventory.ledger_enabled'))->toBeFalse();

    $order = p05ConfirmedOrder($this->user, $this->vendor, $this->warehouse, $this->product, 10);
    $line = $order->lines->first();

    $receipt = p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 4],
    ]);

    expect($receipt)->toBeInstanceOf(PurchaseReceipt::class)
        ->and((float) $line->fresh()->quantity_received)->toBe(4.0)
        ->and($order->fresh()->status)->toBe(PurchaseOrderStatus::Confirmed)
        ->and(StockLevel::query()->count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0)
        ->and($this->product->fresh()->stock_quantity)->toBe(100);
});

test('flag off full receive transitions confirmed to received without stock posts', function () {
    $order = p05ConfirmedOrder($this->user, $this->vendor, $this->warehouse, $this->product, 5);
    $line = $order->lines->first();

    p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 5],
    ]);

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received)
        ->and(StockMovement::query()->count())->toBe(0)
        ->and($this->product->fresh()->stock_quantity)->toBe(100);
});

test('flag on full receive posts purchase_receipt and updates mirror', function () {
    config(['inventory.ledger_enabled' => true]);

    $order = p05ConfirmedOrder($this->user, $this->vendor, $this->warehouse, $this->product, 8);
    $line = $order->lines->first();

    p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 8],
    ]);

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received)
        ->and((int) StockLevel::query()->where('warehouse_id', $this->warehouse->id)->where('product_id', $this->product->id)->value('on_hand'))->toBe(8)
        ->and(StockMovement::query()->where('movement_type', StockMovementType::PurchaseReceipt)->count())->toBe(1)
        ->and($this->product->fresh()->stock_quantity)->toBe(8);
});

test('flag on partial and multiple partial receives accumulate stock', function () {
    config(['inventory.ledger_enabled' => true]);

    $order = p05ConfirmedOrder($this->user, $this->vendor, $this->warehouse, $this->product, 10);
    $line = $order->lines->first();

    p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 3],
    ], null, 'recv-a');

    p05Receipts()->receive($order->fresh(), $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 4],
    ], null, 'recv-b');

    expect((float) $line->fresh()->quantity_received)->toBe(7.0)
        ->and($order->fresh()->status)->toBe(PurchaseOrderStatus::Confirmed)
        ->and((int) StockLevel::query()->value('on_hand'))->toBe(7)
        ->and(StockMovement::query()->where('movement_type', StockMovementType::PurchaseReceipt)->count())->toBe(2)
        ->and($this->product->fresh()->stock_quantity)->toBe(7);

    p05Receipts()->receive($order->fresh(), $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 3],
    ], null, 'recv-c');

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received)
        ->and((int) StockLevel::query()->value('on_hand'))->toBe(10)
        ->and($this->product->fresh()->stock_quantity)->toBe(10);
});

test('receive idempotency key never double posts', function () {
    config(['inventory.ledger_enabled' => true]);

    $order = p05ConfirmedOrder($this->user, $this->vendor, $this->warehouse, $this->product, 6);
    $line = $order->lines->first();
    $key = 'idem-receive-'.Str::uuid();

    $first = p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 2],
    ], null, $key);

    $second = p05Receipts()->receive($order->fresh(), $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 2],
    ], null, $key);

    expect($second->id)->toBe($first->id)
        ->and(PurchaseReceipt::query()->count())->toBe(1)
        ->and((float) $line->fresh()->quantity_received)->toBe(2.0)
        ->and(StockMovement::query()->count())->toBe(1)
        ->and((int) StockLevel::query()->value('on_hand'))->toBe(2);
});

test('return reduces stock and cannot exceed received quantity', function () {
    config(['inventory.ledger_enabled' => true]);

    $order = p05ConfirmedOrder($this->user, $this->vendor, $this->warehouse, $this->product, 10);
    $line = $order->lines->first();

    p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 6],
    ]);

    $return = p05Receipts()->returnGoods($order->fresh(), $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 2],
    ]);

    expect($return)->toBeInstanceOf(PurchaseReturn::class)
        ->and((float) $line->fresh()->quantity_returned)->toBe(2.0)
        ->and((int) StockLevel::query()->value('on_hand'))->toBe(4)
        ->and(StockMovement::query()->where('movement_type', StockMovementType::PurchaseReturn)->count())->toBe(1)
        ->and($this->product->fresh()->stock_quantity)->toBe(4);

    expect(fn () => p05Receipts()->returnGoods($order->fresh(), $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 5],
    ]))->toThrow(ValidationException::class);
});

test('return idempotency key never double posts', function () {
    config(['inventory.ledger_enabled' => true]);

    $order = p05ConfirmedOrder($this->user, $this->vendor, $this->warehouse, $this->product, 5);
    $line = $order->lines->first();

    p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 5],
    ]);

    $key = 'idem-return-'.Str::uuid();

    $first = p05Receipts()->returnGoods($order->fresh(), $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 1],
    ], null, $key);

    $second = p05Receipts()->returnGoods($order->fresh(), $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 1],
    ], null, $key);

    expect($second->id)->toBe($first->id)
        ->and(PurchaseReturn::query()->count())->toBe(1)
        ->and((float) $line->fresh()->quantity_returned)->toBe(1.0)
        ->and(StockMovement::query()->where('movement_type', StockMovementType::PurchaseReturn)->count())->toBe(1);
});

test('validation rejects missing warehouse when ledger enabled', function () {
    config(['inventory.ledger_enabled' => true]);

    $order = p05Orders()->create($this->user, [
        'reference_number' => 'PO-NO-WH',
        'contact_id' => $this->vendor->id,
        'warehouse_id' => null,
        'order_date' => now()->toDateString(),
    ], [[
        'product_id' => $this->product->id,
        'description' => $this->product->name,
        'quantity' => 3,
        'unit_price' => 10,
        'discount_amount' => 0,
        'tax_amount' => 0,
    ]]);
    $order = p05Orders()->confirm($order, $this->user);
    $line = $order->lines->first();

    expect(fn () => p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 1],
    ]))->toThrow(ValidationException::class);
});

test('validation rejects inactive warehouse when ledger enabled', function () {
    config(['inventory.ledger_enabled' => true]);
    $this->warehouse->update(['is_active' => false]);

    $order = p05ConfirmedOrder($this->user, $this->vendor, $this->warehouse, $this->product, 3);
    $line = $order->lines->first();

    expect(fn () => p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 1],
    ]))->toThrow(ValidationException::class);
});

test('validation rejects over-receive invalid qty and fractional qty', function () {
    $order = p05ConfirmedOrder($this->user, $this->vendor, $this->warehouse, $this->product, 4);
    $line = $order->lines->first();

    expect(fn () => p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 5],
    ]))->toThrow(ValidationException::class);

    expect(fn () => p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 0],
    ]))->toThrow(ValidationException::class);

    expect(fn () => p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 1.5],
    ]))->toThrow(ValidationException::class);
});

test('flag off return updates counters without ledger', function () {
    expect(config('inventory.ledger_enabled'))->toBeFalse();

    $order = p05ConfirmedOrder($this->user, $this->vendor, $this->warehouse, $this->product, 5);
    $line = $order->lines->first();

    p05Receipts()->receive($order, $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 5],
    ]);

    p05Receipts()->returnGoods($order->fresh(), $this->user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 2],
    ]);

    expect((float) $line->fresh()->quantity_returned)->toBe(2.0)
        ->and(StockLevel::query()->count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0)
        ->and($this->product->fresh()->stock_quantity)->toBe(100);
});

test('purchase ledger preflight is read-only and never enables ledger', function () {
    expect(config('inventory.ledger_enabled'))->toBeFalse();

    $beforeReceipts = PurchaseReceipt::query()->count();
    $result = app(PurchaseLedgerPreflightService::class)->check();

    expect($result->ledgerEnabled)->toBeFalse()
        ->and(config('inventory.ledger_enabled'))->toBeFalse()
        ->and(PurchaseReceipt::query()->count())->toBe($beforeReceipts);

    $exit = Artisan::call('scf:inventory-purchase-ledger-preflight', ['--json' => true]);
    expect($exit)->toBeIn([0, 1])
        ->and(config('inventory.ledger_enabled'))->toBeFalse();
});

test('inventory.ledger_enabled remains false by default after P0.5 suite', function () {
    expect(config('inventory.ledger_enabled'))->toBeFalse();
});
