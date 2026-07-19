<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Livewire\Livewire;

beforeEach(fn () => actingAsSuperAdmin());

test('invoices index is displayed', function () {
    Invoice::factory()->count(2)->create();

    $this->get(route('invoices.index'))->assertOk();
});

test('invoice can be stored via controller', function () {
    $this->post(route('invoices.store'), [
        'reference_number' => 'INV-TEST-001',
        'invoice_date' => now()->toDateString(),
        'status' => InvoiceStatus::Draft->value,
        'total_amount' => 500,
        'tax_amount' => 50,
        'notes' => 'Invoice test',
    ])->assertRedirect(route('invoices.index'));

    expect(Invoice::query()->where('reference_number', 'INV-TEST-001')->exists())->toBeTrue();
});

test('invoice can be updated and deleted', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::Draft,
    ]);

    $this->put(route('invoices.update', $invoice), [
        'reference_number' => $invoice->reference_number,
        'invoice_date' => $invoice->invoice_date->toDateString(),
        'status' => InvoiceStatus::Sent->value,
        'total_amount' => 900,
        'tax_amount' => 90,
    ])->assertRedirect(route('invoices.index'));

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);

    Livewire::test('pages::sales.invoices-index')
        ->call('confirmDelete', $invoice->id)
        ->call('deleteInvoice')
        ->assertHasNoErrors();

    expect(Invoice::query()->find($invoice->id))->toBeNull();
});

test('cashier can read invoices but cannot open users administration', function () {
    actingAsRole('cashier');

    $this->get(route('invoices.index'))->assertOk();
    $this->get(route('users.index'))->assertForbidden();
});
