<?php

use App\Enums\BillStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SupplierResponseStatus;
use App\Models\Bill;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\PortalSupplier;
use App\Models\PurchaseOrder;
use Database\Seeders\PortalSupplierSeeder;
use Livewire\Livewire;

test('supplier guests are redirected to supplier login', function () {
    $this->get(route('supplier.dashboard'))->assertRedirect(route('supplier.login'));
});

test('supplier can login and reach dashboard', function () {
    $supplier = PortalSupplier::factory()->create([
        'email' => 'supplier-login@example.com',
        'password' => 'password',
    ]);

    $this->post(route('supplier.login.store'), [
        'email' => $supplier->email,
        'password' => 'password',
    ])->assertRedirect(route('supplier.dashboard'));

    $this->assertAuthenticatedAs($supplier, 'supplier');
    $this->get(route('supplier.dashboard'))->assertOk();
});

test('inactive supplier cannot login', function () {
    $supplier = PortalSupplier::factory()->inactive()->create([
        'password' => 'password',
    ]);

    $this->post(route('supplier.login.store'), [
        'email' => $supplier->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('supplier');
});

test('supplier only sees own purchase orders and bills', function () {
    $supplier = actingAsPortalSupplier();
    $other = Contact::factory()->supplier()->create();

    $ownOrder = PurchaseOrder::factory()->create([
        'contact_id' => $supplier->contact_id,
        'status' => PurchaseOrderStatus::Confirmed,
    ]);
    $foreignOrder = PurchaseOrder::factory()->create([
        'contact_id' => $other->id,
        'status' => PurchaseOrderStatus::Confirmed,
    ]);

    $ownBill = Bill::factory()->create([
        'contact_id' => $supplier->contact_id,
        'status' => BillStatus::Received,
    ]);
    $foreignBill = Bill::factory()->create([
        'contact_id' => $other->id,
        'status' => BillStatus::Received,
    ]);

    $this->get(route('supplier.purchase-orders.show', $ownOrder))->assertOk();
    $this->get(route('supplier.purchase-orders.show', $foreignOrder))->assertForbidden();

    $this->get(route('supplier.bills.show', $ownBill))->assertOk();
    $this->get(route('supplier.bills.show', $foreignBill))->assertForbidden();
});

test('supplier can accept own purchase orders only', function () {
    $supplier = actingAsPortalSupplier();
    $other = Contact::factory()->supplier()->create();

    $own = PurchaseOrder::factory()->create([
        'contact_id' => $supplier->contact_id,
        'status' => PurchaseOrderStatus::Confirmed,
        'supplier_response' => null,
    ]);
    $foreign = PurchaseOrder::factory()->create([
        'contact_id' => $other->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'supplier_response' => null,
    ]);

    Livewire::actingAs($supplier, 'supplier')
        ->test('pages::supplier.purchase-orders-index')
        ->call('accept', $own->id)
        ->assertHasNoErrors();

    expect($own->fresh()->supplier_response)->toBe(SupplierResponseStatus::Accepted);

    Livewire::actingAs($supplier, 'supplier')
        ->test('pages::supplier.purchase-orders-index')
        ->call('reject', $foreign->id)
        ->assertForbidden();
});

test('supplier can confirm shipment for own purchase order', function () {
    $supplier = actingAsPortalSupplier();

    $order = PurchaseOrder::factory()->create([
        'contact_id' => $supplier->contact_id,
        'status' => PurchaseOrderStatus::Confirmed,
        'supplier_response' => SupplierResponseStatus::Accepted,
        'expected_date' => now()->addDays(5),
    ]);

    Livewire::actingAs($supplier, 'supplier')
        ->test('pages::supplier.deliveries-index')
        ->call('openConfirm', $order->id)
        ->set('carrier', 'DHL')
        ->set('tracking_number', 'TRACK123')
        ->call('confirmShipment')
        ->assertHasNoErrors();

    expect($order->shipments()->count())->toBe(1)
        ->and($order->shipments()->first()->tracking_number)->toBe('TRACK123');
});

test('supplier print and pdf are scoped to contact ownership', function () {
    $supplier = actingAsPortalSupplier();
    $other = Contact::factory()->supplier()->create();

    $own = Bill::factory()->create([
        'contact_id' => $supplier->contact_id,
        'status' => BillStatus::Received,
    ]);
    $foreign = Bill::factory()->create([
        'contact_id' => $other->id,
        'status' => BillStatus::Received,
    ]);

    $this->get(route('supplier.print', ['type' => 'bill', 'id' => $own->id]))->assertOk();
    $this->get(route('supplier.print', ['type' => 'bill', 'id' => $foreign->id]))->assertNotFound();

    $this->get(route('supplier.pdf', ['type' => 'bill', 'id' => $own->id]))
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('supplier payments page shows outstanding balance', function () {
    $supplier = actingAsPortalSupplier();

    Bill::factory()->create([
        'contact_id' => $supplier->contact_id,
        'status' => BillStatus::Received,
        'total_amount' => 300,
    ]);
    Payment::factory()->create([
        'contact_id' => $supplier->contact_id,
        'type' => PaymentType::Outgoing,
        'amount' => 100,
    ]);

    $this->get(route('supplier.payments.index'))
        ->assertOk()
        ->assertSee('200.00');
});

test('supplier seeder creates demo supplier account', function () {
    $this->seed(PortalSupplierSeeder::class);

    expect(PortalSupplier::query()->where('email', 'supplier@scf.com')->exists())->toBeTrue();
});

test('unverified supplier is redirected to verification notice', function () {
    $supplier = PortalSupplier::factory()->unverified()->create();
    $this->actingAs($supplier, 'supplier');

    $this->get(route('supplier.dashboard'))
        ->assertRedirect(route('supplier.verification.notice'));
});

test('supplier with two factor enabled is challenged after password login', function () {
    $supplier = PortalSupplier::factory()->withTwoFactor()->create([
        'password' => 'password',
    ]);

    $this->post(route('supplier.login.store'), [
        'email' => $supplier->email,
        'password' => 'password',
    ])->assertRedirect(route('supplier.two-factor.login'));

    $this->assertGuest('supplier');

    $this->post(route('supplier.two-factor.login.store'), [
        'recovery_code' => 'recovery-code-1',
    ])->assertRedirect(route('supplier.dashboard'));

    $this->assertAuthenticatedAs($supplier, 'supplier');
});
