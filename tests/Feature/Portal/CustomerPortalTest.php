<?php

use App\Enums\InvoiceStatus;
use App\Enums\QuotationStatus;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PortalCustomer;
use App\Models\Quotation;
use App\Models\SaleOrder;
use App\Models\Ticket;
use Database\Seeders\PortalCustomerSeeder;
use Livewire\Livewire;

test('portal guests are redirected to portal login', function () {
    $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
});

test('portal customer can login and reach dashboard', function () {
    $customer = PortalCustomer::factory()->create([
        'email' => 'portal-login@example.com',
        'password' => 'password',
    ]);

    $this->post(route('portal.login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect(route('portal.dashboard'));

    $this->assertAuthenticatedAs($customer, 'portal');
    $this->get(route('portal.dashboard'))->assertOk();
});

test('inactive portal customer cannot login', function () {
    $customer = PortalCustomer::factory()->inactive()->create([
        'password' => 'password',
    ]);

    $this->post(route('portal.login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('portal');
});

test('portal customer only sees own orders and invoices', function () {
    $customer = actingAsPortalCustomer();
    $other = Contact::factory()->customer()->create();

    $ownOrder = SaleOrder::factory()->create(['contact_id' => $customer->contact_id]);
    $foreignOrder = SaleOrder::factory()->create(['contact_id' => $other->id]);

    $ownInvoice = Invoice::factory()->create([
        'contact_id' => $customer->contact_id,
        'status' => InvoiceStatus::Sent,
    ]);
    $foreignInvoice = Invoice::factory()->create([
        'contact_id' => $other->id,
        'status' => InvoiceStatus::Sent,
    ]);

    $this->get(route('portal.orders.show', $ownOrder))->assertOk();
    $this->get(route('portal.orders.show', $foreignOrder))->assertForbidden();

    $this->get(route('portal.invoices.show', $ownInvoice))->assertOk();
    $this->get(route('portal.invoices.show', $foreignInvoice))->assertForbidden();
});

test('portal customer can accept and reject own quotations only', function () {
    $customer = actingAsPortalCustomer();
    $other = Contact::factory()->customer()->create();

    $own = Quotation::factory()->create([
        'contact_id' => $customer->contact_id,
        'status' => QuotationStatus::Sent,
    ]);
    $foreign = Quotation::factory()->create([
        'contact_id' => $other->id,
        'status' => QuotationStatus::Sent,
    ]);

    Livewire::actingAs($customer, 'portal')
        ->test('pages::portal.quotations-index')
        ->call('accept', $own->id)
        ->assertHasNoErrors();

    expect($own->fresh()->status)->toBe(QuotationStatus::Accepted);

    Livewire::actingAs($customer, 'portal')
        ->test('pages::portal.quotations-index')
        ->call('reject', $foreign->id)
        ->assertForbidden();
});

test('portal customer can create and reply to own tickets', function () {
    $customer = actingAsPortalCustomer();

    Livewire::actingAs($customer, 'portal')
        ->test('pages::portal.tickets-create')
        ->set('subject', 'Need help with invoice')
        ->set('priority', 'high')
        ->set('description', 'Please clarify line items.')
        ->call('save')
        ->assertHasNoErrors();

    $ticket = Ticket::query()->where('contact_id', $customer->contact_id)->first();
    expect($ticket)->not->toBeNull();

    Livewire::actingAs($customer, 'portal')
        ->test('pages::portal.tickets-show', ['ticket' => $ticket])
        ->set('replyBody', 'Additional details attached.')
        ->call('reply')
        ->assertHasNoErrors();

    expect($ticket->fresh()->replies()->count())->toBe(1);
});

test('portal customer cannot open another customers ticket', function () {
    $customer = actingAsPortalCustomer();
    $foreign = Ticket::factory()->create([
        'contact_id' => Contact::factory()->customer()->create()->id,
    ]);

    $this->get(route('portal.tickets.show', $foreign))->assertForbidden();
});

test('portal print and pdf are scoped to contact ownership', function () {
    $customer = actingAsPortalCustomer();
    $other = Contact::factory()->customer()->create();

    $own = Invoice::factory()->create([
        'contact_id' => $customer->contact_id,
        'status' => InvoiceStatus::Sent,
    ]);
    $foreign = Invoice::factory()->create([
        'contact_id' => $other->id,
        'status' => InvoiceStatus::Sent,
    ]);

    $this->get(route('portal.print', ['type' => 'invoice', 'id' => $own->id]))->assertOk();
    $this->get(route('portal.print', ['type' => 'invoice', 'id' => $foreign->id]))->assertNotFound();

    $this->get(route('portal.pdf', ['type' => 'invoice', 'id' => $own->id]))
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('portal payments page shows remaining balance for authenticated customer', function () {
    $customer = actingAsPortalCustomer();

    Invoice::factory()->create([
        'contact_id' => $customer->contact_id,
        'status' => InvoiceStatus::Sent,
        'total_amount' => 200,
    ]);
    Payment::factory()->create([
        'contact_id' => $customer->contact_id,
        'type' => \App\Enums\PaymentType::Incoming,
        'amount' => 50,
    ]);

    $this->get(route('portal.payments.index'))
        ->assertOk()
        ->assertSee('150.00');
});

test('portal seeder creates demo customer account', function () {
    $this->seed(PortalCustomerSeeder::class);

    expect(PortalCustomer::query()->where('email', 'customer@scf.com')->exists())->toBeTrue();
});

test('unverified portal customer is redirected to verification notice', function () {
    $customer = PortalCustomer::factory()->unverified()->create();
    $this->actingAs($customer, 'portal');

    $this->get(route('portal.dashboard'))
        ->assertRedirect(route('portal.verification.notice'));
});

test('portal customer with two factor enabled is challenged after password login', function () {
    $customer = PortalCustomer::factory()->withTwoFactor()->create([
        'password' => 'password',
    ]);

    $this->post(route('portal.login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect(route('portal.two-factor.login'));

    $this->assertGuest('portal');

    $this->post(route('portal.two-factor.login.store'), [
        'recovery_code' => 'recovery-code-1',
    ])->assertRedirect(route('portal.dashboard'));

    $this->assertAuthenticatedAs($customer, 'portal');
});
