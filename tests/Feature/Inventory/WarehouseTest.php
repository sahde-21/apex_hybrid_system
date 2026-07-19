<?php

use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(fn () => actingAsSuperAdmin());

test('warehouses index is displayed', function () {
    Warehouse::factory()->count(2)->create();

    $this->get(route('warehouses.index'))->assertOk();
});

test('warehouse can be stored via controller', function () {
    $this->post(route('warehouses.store'), [
        'name' => 'Main Depot',
        'code' => 'WH-MAIN',
        'address' => '100 Warehouse Rd',
        'phone' => '+1 555-1000',
        'is_active' => true,
    ])->assertRedirect(route('warehouses.index'));

    expect(Warehouse::query()->where('code', 'WH-MAIN')->exists())->toBeTrue();
});

test('warehouse can be updated and deleted', function () {
    $warehouse = Warehouse::factory()->create(['name' => 'Old Name']);

    $this->put(route('warehouses.update', $warehouse), [
        'name' => 'New Name',
        'code' => $warehouse->code,
        'address' => 'Updated address',
        'phone' => null,
        'is_active' => true,
    ])->assertRedirect(route('warehouses.index'));

    expect($warehouse->fresh()->name)->toBe('New Name');

    Livewire::test('pages::inventory.warehouses-index')
        ->call('confirmDelete', $warehouse->id)
        ->call('deleteWarehouse')
        ->assertHasNoErrors();

    expect(Warehouse::query()->find($warehouse->id))->toBeNull();
});

test('warehouse role can manage products and warehouses', function () {
    actingAsRole('warehouse');

    $this->get(route('products.index'))->assertOk();
    $this->get(route('warehouses.index'))->assertOk();
    $this->get(route('sale-orders.index'))->assertForbidden();
});
