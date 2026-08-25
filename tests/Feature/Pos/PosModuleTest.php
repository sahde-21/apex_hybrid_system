<?php

use App\Enums\PosPaymentMethod;
use App\Enums\PosSaleStatus;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\GiftCard;
use App\Models\LoyaltyProgram;
use App\Models\PosFavorite;
use App\Models\PosRegister;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\Variant;
use App\Services\Pos\PosCatalogService;
use App\Services\Pos\PosCheckoutService;
use App\Services\Pos\PosRefundService;
use App\Services\Pos\PosShiftService;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = actingAsSuperAdmin();
    $this->register = PosRegister::factory()->create();
    $this->category = ProductCategory::factory()->create();
    TaxRate::query()->create([
        'name' => 'VAT',
        'code' => 'VAT10',
        'rate' => 10,
        'is_active' => true,
    ]);
});

test('cashier can open pos terminal', function () {
    actingAsRole('cashier');

    $this->get(route('pos.terminal'))->assertOk();
});

test('unauthorized user cannot access pos', function () {
    actingAsRole('hr');

    $this->get(route('pos.terminal'))->assertForbidden();
});

test('shift can be opened and closed with cash summary', function () {
    $shifts = app(PosShiftService::class);

    $shift = $shifts->open($this->register, $this->user, 200);
    expect($shift->isOpen())->toBeTrue();

    $closed = $shifts->close($shift, 200);
    expect($closed->status->value)->toBe('closed')
        ->and((float) $closed->expected_cash)->toBe(200.0);
});

test('catalog finds product by barcode and sku', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'barcode' => '1234567890123',
        'sku' => 'SCAN-ME',
        'stock_quantity' => 20,
        'is_active' => true,
    ]);

    $catalog = app(PosCatalogService::class);

    expect($catalog->findByScan('1234567890123')['product_id'])->toBe($product->id)
        ->and($catalog->findByScan('SCAN-ME')['product_id'])->toBe($product->id);
});

test('catalog finds variant by barcode', function () {
    $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
    $variant = Variant::factory()->create([
        'product_id' => $product->id,
        'barcode' => 'VAR-9988',
        'sku' => 'V-9988',
        'is_active' => true,
        'stock_quantity' => 8,
    ]);

    $found = app(PosCatalogService::class)->findByScan('VAR-9988');

    expect($found['variant_id'])->toBe($variant->id);
});

test('checkout creates invoice payment deducts stock and opens cash drawer meta', function () {
    $product = Product::factory()->create([
        'sale_price' => 100,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    $shift = app(PosShiftService::class)->open($this->register, $this->user, 50);

    $sale = app(PosCheckoutService::class)->checkout(
        shift: $shift,
        user: $this->user,
        items: [[
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => 2,
            'unit_price' => 100,
            'tax_rate' => 10,
        ]],
        payments: [[
            'method' => PosPaymentMethod::Cash->value,
            'amount' => 220,
        ]],
    );

    expect($sale->status)->toBe(PosSaleStatus::Completed)
        ->and((float) $sale->total_amount)->toBe(220.0)
        ->and($sale->invoice)->not->toBeNull()
        ->and($sale->payments)->toHaveCount(1)
        ->and($product->fresh()->stock_quantity)->toBe(8)
        ->and($sale->cash_drawer_opened)->toBeTrue();
});

test('checkout supports split payment coupon gift card and loyalty', function () {
    $product = Product::factory()->create([
        'sale_price' => 200,
        'stock_quantity' => 5,
        'is_active' => true,
    ]);
    $contact = Contact::factory()->create(['type' => 'customer']);
    $coupon = Coupon::factory()->create([
        'code' => 'SAVE10',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'valid_from' => now()->subDay()->toDateString(),
        'valid_until' => now()->addDay()->toDateString(),
        'usage_limit' => 10,
        'usage_count' => 0,
        'is_active' => true,
    ]);
    $giftCard = GiftCard::factory()->create([
        'code' => 'GIFT100',
        'initial_balance' => 100,
        'current_balance' => 100,
        'expires_at' => now()->addMonth()->toDateString(),
        'is_active' => true,
        'contact_id' => $contact->id,
    ]);
    LoyaltyProgram::factory()->create([
        'points_per_currency' => 1,
        'is_active' => true,
    ]);

    $shift = app(PosShiftService::class)->open($this->register, $this->user, 0);

    // 200 - 10% coupon = 180 + 10% tax on discounted base ≈ depends on pricing service
    $sale = app(PosCheckoutService::class)->checkout(
        shift: $shift,
        user: $this->user,
        items: [[
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 200,
            'tax_rate' => 0,
        ]],
        payments: [
            ['method' => PosPaymentMethod::GiftCard->value, 'amount' => 80, 'gift_card_code' => 'GIFT100'],
            ['method' => PosPaymentMethod::Card->value, 'amount' => 100],
        ],
        contactId: $contact->id,
        couponCode: 'SAVE10',
    );

    expect((float) $sale->total_amount)->toBe(180.0)
        ->and($sale->payments)->toHaveCount(2)
        ->and((float) $giftCard->fresh()->current_balance)->toBe(20.0)
        ->and((int) $coupon->fresh()->usage_count)->toBe(1)
        ->and((float) $sale->loyalty_points_earned)->toBeGreaterThan(0);
});

test('refund restocks inventory and marks original sale', function () {
    $product = Product::factory()->create([
        'sale_price' => 50,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    $shift = app(PosShiftService::class)->open($this->register, $this->user, 0);
    $sale = app(PosCheckoutService::class)->checkout(
        shift: $shift,
        user: $this->user,
        items: [[
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50,
            'tax_rate' => 0,
        ]],
        payments: [['method' => 'cash', 'amount' => 100]],
    );

    $refund = app(PosRefundService::class)->refund($sale, $this->user);

    expect($refund->is_return)->toBeTrue()
        ->and($product->fresh()->stock_quantity)->toBe(10)
        ->and($sale->fresh()->status)->toBe(PosSaleStatus::Refunded);
});

test('pos terminal livewire can add product and checkout', function () {
    $product = Product::factory()->create([
        'sale_price' => 25,
        'stock_quantity' => 4,
        'is_active' => true,
        'name' => 'POS Test Item',
    ]);

    $shift = app(PosShiftService::class)->open($this->register, $this->user, 10);

    Livewire::test('pages::pos.terminal')
        ->set('registerId', $this->register->id)
        ->set('shiftId', $shift->id)
        ->call('addProduct', $product->id)
        ->assertSet('cart.0.product_id', $product->id)
        ->call('preparePayment')
        ->assertSet('showPayment', true)
        ->call('checkout')
        ->assertHasNoErrors();

    expect(PosSale::query()->count())->toBe(1)
        ->and($product->fresh()->stock_quantity)->toBe(3);
});

test('favorites can be toggled for pos catalog', function () {
    $product = Product::factory()->create(['is_active' => true]);

    Livewire::test('pages::pos.terminal')
        ->call('toggleFavorite', $product->id);

    expect(PosFavorite::query()->where('user_id', $this->user->id)->where('product_id', $product->id)->exists())->toBeTrue();
});

test('quick customer creation works from pos', function () {
    $customer = app(PosCheckoutService::class)->quickCreateCustomer($this->user, 'Walk In Plus', '0750');

    expect($customer->name)->toBe('Walk In Plus')
        ->and($customer->type->value)->toBe('customer');
});

test('daily summary aggregates sales', function () {
    $product = Product::factory()->create(['sale_price' => 10, 'stock_quantity' => 100, 'is_active' => true]);
    $shift = app(PosShiftService::class)->open($this->register, $this->user, 0);
    app(PosCheckoutService::class)->checkout(
        shift: $shift,
        user: $this->user,
        items: [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10, 'tax_rate' => 0]],
        payments: [['method' => 'cash', 'amount' => 10]],
    );

    $summary = app(PosShiftService::class)->dailySummary(now());

    expect($summary['sales_count'])->toBe(1)
        ->and($summary['gross_sales'])->toBe(10.0);
});

test('pos sales and summary pages render', function () {
    $this->get(route('pos.sales.index'))->assertOk();
    $this->get(route('pos.summary'))->assertOk();
    $this->get(route('pos.shifts.index'))->assertOk();
    $this->get(route('pos.registers.index'))->assertOk();
});

test('pos receipt can be printed thermally and a4', function () {
    $product = Product::factory()->create(['sale_price' => 15, 'stock_quantity' => 5, 'is_active' => true]);
    $shift = app(PosShiftService::class)->open($this->register, $this->user, 0);
    $sale = app(PosCheckoutService::class)->checkout(
        shift: $shift,
        user: $this->user,
        items: [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 15, 'tax_rate' => 0]],
        payments: [['method' => 'cash', 'amount' => 15]],
    );

    $this->get(route('print.document', ['type' => 'pos-sale', 'id' => $sale->id, 'layout' => 'thermal_80mm']))
        ->assertOk();
    $this->get(route('print.document', ['type' => 'pos-sale', 'id' => $sale->id, 'layout' => 'a4']))
        ->assertOk();
});
