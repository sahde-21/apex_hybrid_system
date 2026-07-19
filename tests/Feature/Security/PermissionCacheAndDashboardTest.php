<?php

use App\Models\Product;
use App\Models\User;
use App\Support\PermissionCache;
use App\Support\PostLoginRedirect;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('stale empty permission cache is healed and dashboard is allowed', function () {
    $registrar = app(PermissionRegistrar::class);
    $registrar->forgetCachedPermissions();

    // Simulate the production failure mode: empty permissions in cache while DB has records.
    $registrar->getCacheRepository()->forever(config('permission.cache.key'), [
        'alias' => [],
        'permissions' => [],
        'roles' => [],
    ]);

    PermissionCache::healIfStale();

    $user = User::factory()->create();
    $user->assignRole('super-admin');
    $this->actingAs($user);

    expect($user->can('dashboard.read'))->toBeTrue();
    $this->get(route('dashboard'))->assertOk();
});

test('super-admin with synced role permissions can open dashboard', function () {
    $user = User::where('email', 'admin@scf.com')->first() ?? User::factory()->create(['email' => 'admin@scf.com']);
    $user->syncRoles(['super-admin']);
    $this->actingAs($user);

    expect($user->getAllPermissions()->count())->toBeGreaterThan(0)
        ->and($user->can('dashboard.read'))->toBeTrue();

    $this->get(route('dashboard'))->assertOk();
});

test('post login redirect prefers dashboard when permitted else falls back', function () {
    $withDashboard = User::factory()->create();
    $withDashboard->assignRole('cashier');

    expect(PostLoginRedirect::url($withDashboard))->toEndWith('/dashboard');

    $withoutDashboard = User::factory()->create();
    $withoutDashboard->givePermissionTo(['products.read', 'products.create']);

    expect(PostLoginRedirect::url($withoutDashboard))->toEndWith('/inventory/products');
});

test('livewire product delete requires products.delete permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['dashboard.read', 'products.read', 'products.create', 'products.update']);
    $this->actingAs($user);

    $product = Product::factory()->create();

    Livewire::test('pages::inventory.products-index')
        ->call('confirmDelete', $product->id)
        ->call('deleteProduct')
        ->assertForbidden();

    expect(Product::query()->whereKey($product->id)->exists())->toBeTrue();
});
