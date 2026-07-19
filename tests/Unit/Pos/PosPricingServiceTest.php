<?php

use App\Models\Coupon;
use App\Services\Pos\PosPricingService;

it('calculates line tax discount and coupon percentage', function () {
    $coupon = new Coupon([
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'is_active' => true,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDay(),
        'usage_limit' => 10,
        'usage_count' => 0,
    ]);

    $totals = app(PosPricingService::class)->calculate(
        items: [
            ['unit_price' => 100, 'quantity' => 2, 'tax_rate' => 10],
        ],
        cartDiscount: 0,
        coupon: $coupon,
        defaultTaxRate: 10,
    );

    expect($totals['subtotal'])->toBe(200.0)
        ->and($totals['coupon_discount'])->toBe(20.0)
        ->and($totals['discount_total'])->toBe(20.0)
        ->and($totals['total'])->toBeGreaterThan(0);
});

it('applies fixed coupon discount', function () {
    $coupon = Coupon::factory()->make([
        'discount_type' => 'fixed',
        'discount_value' => 15,
        'is_active' => true,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDay(),
        'usage_limit' => 5,
        'usage_count' => 0,
    ]);

    // Ensure redeemable without persistence
    $coupon->exists = false;

    $service = app(PosPricingService::class);
    $discount = $service->couponDiscount($coupon, 100);

    expect($discount)->toBe(15.0);
});
