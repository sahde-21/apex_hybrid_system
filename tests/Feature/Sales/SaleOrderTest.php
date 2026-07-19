<?php

use App\Enums\SaleOrderStatus;
use App\Models\SaleOrder;
use Livewire\Livewire;

beforeEach(fn () => actingAsSuperAdmin());

test('sale orders index is displayed', function () {
    SaleOrder::factory()->count(2)->create();

    $this->get(route('sale-orders.index'))->assertOk();
});

test('sale order can be stored via controller', function () {
    $this->post(route('sale-orders.store'), [
        'reference_number' => 'SO-TEST-001',
        'order_date' => now()->toDateString(),
        'status' => SaleOrderStatus::Draft->value,
        'total_amount' => 250.50,
        'notes' => 'Sale order test',
    ])->assertRedirect(route('sale-orders.index'));

    expect(SaleOrder::query()->where('reference_number', 'SO-TEST-001')->exists())->toBeTrue();
});

test('sale order can be updated via controller', function () {
    $order = SaleOrder::factory()->create([
        'status' => SaleOrderStatus::Draft,
        'total_amount' => 100,
    ]);

    $this->put(route('sale-orders.update', $order), [
        'reference_number' => $order->reference_number,
        'order_date' => $order->order_date->toDateString(),
        'status' => SaleOrderStatus::Confirmed->value,
        'total_amount' => 175.25,
        'notes' => 'Updated',
    ])->assertRedirect(route('sale-orders.index'));

    expect($order->fresh()->status)->toBe(SaleOrderStatus::Confirmed)
        ->and((float) $order->fresh()->total_amount)->toBe(175.25);
});

test('sale order can be deleted via livewire', function () {
    $order = SaleOrder::factory()->create();

    Livewire::test('pages::sales.sale-orders-index')
        ->call('confirmDelete', $order->id)
        ->call('deleteSaleOrder')
        ->assertHasNoErrors();

    expect(SaleOrder::query()->find($order->id))->toBeNull();
});

test('sales role can create sale orders but cannot delete them', function () {
    actingAsRole('sales');

    $this->post(route('sale-orders.store'), [
        'reference_number' => 'SO-SALES-001',
        'order_date' => now()->toDateString(),
        'status' => SaleOrderStatus::Draft->value,
        'total_amount' => 50,
    ])->assertRedirect(route('sale-orders.index'));

    $order = SaleOrder::query()->where('reference_number', 'SO-SALES-001')->firstOrFail();

    $this->delete(route('sale-orders.destroy', $order))->assertForbidden();
});
