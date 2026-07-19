<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $this->actingAs($user);
});

test('products index page is displayed', function () {
    $this->get(route('products.index'))->assertOk();
});

test('product can be created via livewire', function () {
    Livewire::test('pages::inventory.products-create')
        ->set('name', 'Test Product')
        ->set('sku', 'SKU-001')
        ->set('description', 'A test product description')
        ->set('purchase_price', '10.00')
        ->set('sale_price', '15.00')
        ->set('stock_quantity', 100)
        ->set('minimum_stock_level', 10)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('products.index'));

    expect(Product::query()->where('sku', 'SKU-001')->exists())->toBeTrue();
});

test('product can be updated via livewire', function () {
    $product = Product::factory()->create(['name' => 'Original Name']);

    Livewire::test('pages::inventory.products-edit', ['product' => $product])
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('products.index'));

    expect($product->fresh()->name)->toBe('Updated Name');
});

test('product can be deleted via livewire', function () {
    $product = Product::factory()->create();

    Livewire::test('pages::inventory.products-index')
        ->call('confirmDelete', $product->id)
        ->call('deleteProduct')
        ->assertHasNoErrors();

    expect(Product::query()->find($product->id))->toBeNull();
});

test('product can be stored via controller', function () {
    $this->post(route('products.store'), [
        'name' => 'Controller Product',
        'sku' => 'SKU-CTRL',
        'description' => 'Created via controller',
        'purchase_price' => 5.00,
        'sale_price' => 9.99,
        'stock_quantity' => 50,
        'minimum_stock_level' => 5,
    ])->assertRedirect(route('products.index'));

    expect(Product::query()->where('sku', 'SKU-CTRL')->exists())->toBeTrue();
});

test('low stock filter shows only low stock products', function () {
    $lowStock = Product::factory()->create(['stock_quantity' => 5, 'minimum_stock_level' => 10]);
    $inStock = Product::factory()->create(['stock_quantity' => 100, 'minimum_stock_level' => 10]);

    Livewire::test('pages::inventory.products-index')
        ->set('lowStockOnly', true)
        ->assertSee($lowStock->name)
        ->assertSee('Low stock')
        ->assertDontSee($inStock->name);
});
