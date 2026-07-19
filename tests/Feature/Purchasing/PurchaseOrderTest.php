<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Livewire\Livewire;

beforeEach(fn () => actingAsSuperAdmin());

test('purchase orders index is displayed', function () {
    PurchaseOrder::factory()->count(2)->create();

    $this->get(route('purchase-orders.index'))->assertOk();
});

test('purchase order can be stored via controller', function () {
    $this->post(route('purchase-orders.store'), [
        'reference_number' => 'PO-TEST-001',
        'order_date' => now()->toDateString(),
        'status' => PurchaseOrderStatus::Draft->value,
        'total_amount' => 1200,
        'notes' => 'PO test',
    ])->assertRedirect(route('purchase-orders.index'));

    expect(PurchaseOrder::query()->where('reference_number', 'PO-TEST-001')->exists())->toBeTrue();
});

test('purchase order can be updated via controller', function () {
    $order = PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::Draft,
    ]);

    $this->put(route('purchase-orders.update', $order), [
        'reference_number' => $order->reference_number,
        'order_date' => $order->order_date->toDateString(),
        'status' => PurchaseOrderStatus::Confirmed->value,
        'total_amount' => 1500,
    ])->assertRedirect(route('purchase-orders.index'));

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Confirmed);
});

test('purchase order can be deleted via livewire', function () {
    $order = PurchaseOrder::factory()->create();

    Livewire::test('pages::purchasing.purchase-orders-index')
        ->call('confirmDelete', $order->id)
        ->call('deletePurchaseOrder')
        ->assertHasNoErrors();

    expect(PurchaseOrder::query()->find($order->id))->toBeNull();
});

test('purchasing role can create purchase orders but not delete them', function () {
    actingAsRole('purchasing');

    $this->post(route('purchase-orders.store'), [
        'reference_number' => 'PO-BUY-001',
        'order_date' => now()->toDateString(),
        'status' => PurchaseOrderStatus::Draft->value,
        'total_amount' => 80,
    ])->assertRedirect(route('purchase-orders.index'));

    $order = PurchaseOrder::query()->where('reference_number', 'PO-BUY-001')->firstOrFail();
    $this->delete(route('purchase-orders.destroy', $order))->assertForbidden();
});
