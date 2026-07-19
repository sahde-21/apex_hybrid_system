<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\App;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');
    $this->actingAs($user);
});

it('switches locale to arabic via query string and persists in session', function () {
    $this->get(route('dashboard', ['lang' => 'ar']))
        ->assertOk()
        ->assertSessionHas('locale', 'ar');

    expect(App::getLocale())->toBe('ar');
});

it('switches locale to kurdish via query string', function () {
    $this->get(route('dashboard', ['lang' => 'ckb']))
        ->assertOk()
        ->assertSessionHas('locale', 'ckb');

    expect(App::getLocale())->toBe('ckb');
});

it('switches locale back to english', function () {
    $this->withSession(['locale' => 'ar'])
        ->get(route('dashboard', ['lang' => 'en']))
        ->assertOk()
        ->assertSessionHas('locale', 'en');

    expect(App::getLocale())->toBe('en');
});

it('ignores unsupported locales', function () {
    $this->withSession(['locale' => 'en'])
        ->get(route('dashboard', ['lang' => 'xx']))
        ->assertOk()
        ->assertSessionHas('locale', 'en');

    expect(App::getLocale())->toBe('en');
});
