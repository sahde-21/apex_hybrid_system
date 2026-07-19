<?php

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SaleOrder;

beforeEach(fn () => actingAsSuperAdmin());

it('prints supported document types for super-admin', function (string $type, string $model) {
    $record = $model::factory()->create();

    $this->get(route('print.document', [
        'type' => $type,
        'id' => $record->id,
        'layout' => 'a4',
    ]))->assertOk();
})->with([
    'invoice' => ['invoice', Invoice::class],
    'payment' => ['payment', Payment::class],
    'sale-order' => ['sale-order', SaleOrder::class],
    'purchase-order' => ['purchase-order', PurchaseOrder::class],
    'bill' => ['bill', Bill::class],
    'quotation' => ['quotation', Quotation::class],
    'expense' => ['expense', Expense::class],
]);

it('rejects unknown print types', function () {
    $this->get(route('print.document', [
        'type' => 'unknown-type',
        'id' => 1,
    ]))->assertNotFound();
});

it('allows thermal layout for invoices', function () {
    $invoice = Invoice::factory()->create();

    $this->get(route('print.document', [
        'type' => 'invoice',
        'id' => $invoice->id,
        'layout' => 'thermal_80mm',
    ]))->assertOk();
});

it('forbids sales role from printing expenses', function () {
    actingAsRole('sales');
    $expense = Expense::factory()->create();

    $this->get(route('print.document', [
        'type' => 'expense',
        'id' => $expense->id,
    ]))->assertForbidden();
});

it('allows sales role to print invoices', function () {
    actingAsRole('sales');
    $invoice = Invoice::factory()->create();

    $this->get(route('print.document', [
        'type' => 'invoice',
        'id' => $invoice->id,
    ]))->assertOk();
});
