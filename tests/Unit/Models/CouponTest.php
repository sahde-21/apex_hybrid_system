<?php

use App\Models\Coupon;
use App\Models\PosSale;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('exposes has-many relations for coupon foreign keys', function () {
    expect((new Coupon)->posSales())->toBeInstanceOf(HasMany::class);
});

it('loads inverse records through coupon relations', function () {
    $coupon = Coupon::factory()->create(['is_active' => true]);
    PosSale::factory()->create(['coupon_id' => $coupon->id]);

    $coupon->load('posSales');

    expect($coupon->posSales)->toHaveCount(1)
        ->and($coupon->posSales->first()->coupon_id)->toBe($coupon->id);
});
