<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\QuotationStatus;
use App\Enums\SaleOrderStatus;
use App\Models\AccountingPosting;
use App\Models\Contact;
use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SaleOrder;
use App\Models\SalesDocumentEvent;
use App\Services\Accounting\FiscalPeriodService;
use App\Services\Sales\InvoiceWorkflowService;
use App\Services\Sales\PaymentWorkflowService;
use App\Services\Sales\QuotationWorkflowService;
use App\Services\Sales\SaleOrderWorkflowService;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    test()->seed(RolePermissionSeeder::class);
    test()->seed(AccountingSeeder::class);
});

function salesLines(float $qty = 2, float $price = 50, float $tax = 10): array
{
    return [[
        'product_id' => null,
        'description' => 'Widget',
        'quantity' => $qty,
        'unit_price' => $price,
        'discount_amount' => 0,
        'tax_amount' => $tax,
    ]];
}

function salesCustomer(): Contact
{
    return Contact::factory()->customer()->create();
}

test('sales workflow localization key parity', function () {
    $en = require lang_path('en/scf.php');
    $ar = require lang_path('ar/scf.php');
    $ckb = require lang_path('ckb/scf.php');

    $enKeys = array_keys($en['sales_workflow']);
    expect(array_keys($ar['sales_workflow']))->toEqual($enKeys)
        ->and(array_keys($ckb['sales_workflow']))->toEqual($enKeys);
});

test('quotation crud send approve reject cancel expire duplicate convert and auth', function () {
    $user = actingAsSuperAdmin();
    $contact = salesCustomer();
    $quotes = app(QuotationWorkflowService::class);

    $quotation = $quotes->create($user, [
        'reference_number' => 'QT-B1-001',
        'contact_id' => $contact->id,
        'quotation_date' => now()->toDateString(),
        'valid_until' => now()->addDays(14)->toDateString(),
    ], salesLines());

    expect($quotation->status)->toBe(QuotationStatus::Draft)
        ->and($quotation->lines)->toHaveCount(1)
        ->and((float) $quotation->total_amount)->toBe(110.0);

    $this->get(route('quotations.show', $quotation))->assertOk();
    $this->get(route('quotations.index'))->assertOk();

    $quotation = $quotes->send($quotation, $user);
    expect($quotation->status)->toBe(QuotationStatus::Sent);

    $quotation = $quotes->approve($quotation, $user, 'Looks good');
    expect($quotation->status)->toBe(QuotationStatus::Accepted);

    $copy = $quotes->duplicate($quotation, $user);
    expect($copy->status)->toBe(QuotationStatus::Draft)
        ->and($copy->id)->not->toBe($quotation->id);

    $order = $quotes->convertToSaleOrder($quotation, $user);
    expect($order)->toBeInstanceOf(SaleOrder::class)
        ->and($quotation->fresh()->status)->toBe(QuotationStatus::Converted)
        ->and($quotation->fresh()->converted_sale_order_id)->toBe($order->id)
        ->and($order->quotation_id)->toBe($quotation->id)
        ->and($order->lines)->toHaveCount(1);

    expect(fn () => $quotes->convertToSaleOrder($quotation->fresh(), $user))
        ->toThrow(ValidationException::class);

    $rejected = $quotes->create($user, [
        'reference_number' => 'QT-B1-REJ',
        'contact_id' => $contact->id,
        'quotation_date' => now()->toDateString(),
    ], salesLines());
    $rejected = $quotes->send($rejected, $user);
    $rejected = $quotes->reject($rejected, $user, 'Too expensive');
    expect($rejected->status)->toBe(QuotationStatus::Rejected);

    $cancelled = $quotes->create($user, [
        'reference_number' => 'QT-B1-CAN',
        'contact_id' => $contact->id,
        'quotation_date' => now()->toDateString(),
    ], salesLines());
    $cancelled = $quotes->cancel($cancelled, $user);
    expect($cancelled->status)->toBe(QuotationStatus::Cancelled);

    $expiring = $quotes->create($user, [
        'reference_number' => 'QT-B1-EXP',
        'contact_id' => $contact->id,
        'quotation_date' => now()->subDays(10)->toDateString(),
        'valid_until' => now()->subDay()->toDateString(),
    ], salesLines());
    $expiring = $quotes->send($expiring, $user);
    $expiring = $quotes->expireIfNeeded($expiring, $user);
    expect($expiring->status)->toBe(QuotationStatus::Expired);

    expect(SalesDocumentEvent::query()->where('document_type', $quotation->getMorphClass())->count())->toBeGreaterThan(0);

    actingAsUserWithPermissions(['quotations.read']);
    expect(fn () => $quotes->send(Quotation::factory()->create(), auth()->user()))
        ->toThrow(HttpException::class);
});

test('sale order submit approve confirm cancel invoice partial and over-invoice guard', function () {
    $user = actingAsSuperAdmin();
    $contact = salesCustomer();
    $orders = app(SaleOrderWorkflowService::class);

    $order = $orders->create($user, [
        'reference_number' => 'SO-B1-001',
        'contact_id' => $contact->id,
        'order_date' => now()->toDateString(),
        'delivery_date' => now()->addWeek()->toDateString(),
        'billing_address' => 'Billing Rd',
        'shipping_address' => 'Ship Ave',
    ], salesLines(10, 20, 0));

    expect($order->status)->toBe(SaleOrderStatus::Draft);
    $this->get(route('sale-orders.show', $order))->assertOk();

    $order = $orders->submit($order, $user);
    expect($order->status)->toBe(SaleOrderStatus::PendingApproval);

    $order = $orders->rejectToDraft($order, $user, 'Fix prices');
    expect($order->status)->toBe(SaleOrderStatus::Draft);

    $order = $orders->submit($order, $user);
    $order = $orders->approve($order, $user);
    expect($order->status)->toBe(SaleOrderStatus::Approved);

    $order = $orders->confirm($order, $user);
    expect($order->status)->toBe(SaleOrderStatus::Confirmed);

    $line = $order->lines()->first();
    $partial = $orders->createInvoice($order, $user, [
        ['sale_order_line_id' => $line->id, 'quantity' => 4],
    ]);
    expect($partial->status)->toBe(InvoiceStatus::Draft)
        ->and((float) $partial->lines()->first()->quantity)->toBe(4.0)
        ->and($order->fresh()->status)->toBe(SaleOrderStatus::PartiallyInvoiced);

    expect(fn () => $orders->createInvoice($order->fresh(), $user, [
        ['sale_order_line_id' => $line->id, 'quantity' => 99],
    ]))->toThrow(ValidationException::class);

    $rest = $orders->createInvoice($order->fresh(), $user);
    expect($order->fresh()->status)->toBe(SaleOrderStatus::Invoiced)
        ->and((float) $line->fresh()->quantity_invoiced)->toBe(10.0);

    $cancelable = $orders->create($user, [
        'reference_number' => 'SO-B1-CAN',
        'contact_id' => $contact->id,
        'order_date' => now()->toDateString(),
    ], salesLines());
    $cancelable = $orders->cancel($cancelable, $user);
    expect($cancelable->status)->toBe(SaleOrderStatus::Cancelled);

    $dup = $orders->duplicate($order, $user);
    expect($dup->status)->toBe(SaleOrderStatus::Draft);

    actingAsUserWithPermissions(['sale-orders.read']);
    expect(fn () => $orders->confirm(SaleOrder::factory()->create(), auth()->user()))
        ->toThrow(HttpException::class);
});

test('invoice issue posts accounting void reverse closed period and payment settlement', function () {
    $user = actingAsSuperAdmin();
    $contact = salesCustomer();
    $invoices = app(InvoiceWorkflowService::class);
    $payments = app(PaymentWorkflowService::class);

    $invoice = $invoices->create($user, [
        'reference_number' => 'INV-B1-001',
        'contact_id' => $contact->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
    ], salesLines(1, 100, 10));

    expect($invoice->status)->toBe(InvoiceStatus::Draft);
    $this->get(route('invoices.show', $invoice))->assertOk();

    expect(fn () => $invoices->update($invoice->fresh(), $user, [
        'reference_number' => 'INV-B1-001',
        'contact_id' => $contact->id,
        'invoice_date' => now()->toDateString(),
    ], salesLines(1, 100, 10)))->not->toThrow(ValidationException::class);

    $invoice = $invoices->issue($invoice->fresh(), $user);
    expect($invoice->status)->toBe(InvoiceStatus::Sent)
        ->and($invoice->issued_at)->not->toBeNull();

    $posting = AccountingPosting::query()
        ->where('source_type', $invoice->getMorphClass())
        ->where('source_id', $invoice->id)
        ->where('event', 'invoice.posted')
        ->first();
    expect($posting)->not->toBeNull()
        ->and($posting->journal_entry_id)->not->toBeNull();

    expect(fn () => $invoices->update($invoice->fresh(), $user, [
        'reference_number' => 'INV-B1-001',
        'contact_id' => $contact->id,
        'invoice_date' => now()->toDateString(),
    ], salesLines()))->toThrow(ValidationException::class);

    Livewire::test('pages::sales.invoices-index')
        ->call('confirmDelete', $invoice->id)
        ->call('deleteInvoice');
    expect(Invoice::query()->find($invoice->id))->not->toBeNull();

    $partial = $payments->create($user, [
        'reference_number' => 'PAY-B1-P1',
        'contact_id' => $contact->id,
        'invoice_id' => $invoice->id,
        'payment_date' => now()->toDateString(),
        'amount' => 50,
        'type' => PaymentType::Incoming->value,
        'payment_method' => 'cash',
    ]);
    expect($partial->status)->toBe(PaymentStatus::Draft);

    $partial = $payments->post($partial, $user);
    expect($partial->status)->toBe(PaymentStatus::Posted)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(50.0);

    expect(fn () => $payments->create($user, [
        'reference_number' => 'PAY-B1-OVER',
        'contact_id' => $contact->id,
        'invoice_id' => $invoice->id,
        'payment_date' => now()->toDateString(),
        'amount' => 9999,
        'type' => PaymentType::Incoming->value,
    ]))->toThrow(ValidationException::class);

    $full = $payments->create($user, [
        'reference_number' => 'PAY-B1-FULL',
        'contact_id' => $contact->id,
        'invoice_id' => $invoice->id,
        'payment_date' => now()->toDateString(),
        'amount' => 60,
        'type' => PaymentType::Incoming->value,
        'payment_method' => 'cash',
    ]);
    $full = $payments->post($full, $user);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    $payments->reverse($full, $user, 'Customer dispute');
    expect($full->fresh()->status)->toBe(PaymentStatus::Reversed)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::PartiallyPaid);

    $overdue = $invoices->create($user, [
        'reference_number' => 'INV-B1-OD',
        'contact_id' => $contact->id,
        'invoice_date' => now()->subDays(40)->toDateString(),
        'due_date' => now()->subDays(10)->toDateString(),
    ], salesLines(1, 40, 0));
    $overdue = $invoices->issue($overdue, $user);
    $overdue = $invoices->markOverdueIfNeeded($overdue);
    expect($overdue->status)->toBe(InvoiceStatus::Overdue);

    $voidable = $invoices->create($user, [
        'reference_number' => 'INV-B1-VOID',
        'contact_id' => $contact->id,
        'invoice_date' => now()->toDateString(),
    ], salesLines(1, 25, 0));
    $voidable = $invoices->issue($voidable, $user);
    $voidable = $invoices->void($voidable, $user, 'Billing error');
    expect($voidable->status)->toBe(InvoiceStatus::Void);

    $draftCancel = $invoices->create($user, [
        'reference_number' => 'INV-B1-DC',
        'contact_id' => $contact->id,
        'invoice_date' => now()->toDateString(),
    ], salesLines(1, 15, 0));
    $draftCancel = $invoices->cancel($draftCancel, $user);
    expect($draftCancel->status)->toBe(InvoiceStatus::Cancelled);

    $period = FiscalPeriod::query()->whereDate('starts_on', '<=', now())->whereDate('ends_on', '>=', now())->first();
    if ($period) {
        app(FiscalPeriodService::class)->closePeriod($period, $user);
        $blocked = $invoices->create($user, [
            'reference_number' => 'INV-B1-CLOSED',
            'contact_id' => $contact->id,
            'invoice_date' => now()->toDateString(),
        ], salesLines(1, 10, 0));
        expect(fn () => $invoices->issue($blocked, $user))->toThrow(ValidationException::class);
        app(FiscalPeriodService::class)->reopenPeriod($period, $user);
    }

    actingAsUserWithPermissions(['invoices.read']);
    expect(fn () => $invoices->issue(Invoice::factory()->create(['contact_id' => $contact->id, 'total_amount' => 10]), auth()->user()))
        ->toThrow(HttpException::class);
});

test('payment workflow authorization and cancel draft', function () {
    $user = actingAsSuperAdmin();
    $contact = salesCustomer();
    $invoices = app(InvoiceWorkflowService::class);
    $payments = app(PaymentWorkflowService::class);

    $invoice = $invoices->create($user, [
        'reference_number' => 'INV-PAY-AUTH',
        'contact_id' => $contact->id,
        'invoice_date' => now()->toDateString(),
    ], salesLines(1, 80, 0));
    $invoice = $invoices->issue($invoice, $user);

    $payment = $payments->create($user, [
        'reference_number' => 'PAY-AUTH-1',
        'contact_id' => $contact->id,
        'invoice_id' => $invoice->id,
        'payment_date' => now()->toDateString(),
        'amount' => 20,
        'type' => PaymentType::Incoming->value,
    ]);
    $this->get(route('payments.show', $payment))->assertOk();
    $payment = $payments->cancel($payment, $user);
    expect($payment->status)->toBe(PaymentStatus::Cancelled);

    actingAsUserWithPermissions(['payments.read']);
    expect(fn () => $payments->post(Payment::factory()->create([
        'status' => PaymentStatus::Draft,
        'type' => PaymentType::Incoming,
        'amount' => 5,
    ]), auth()->user()))->toThrow(HttpException::class);
});

test('document relationships appear across the sales chain', function () {
    $user = actingAsSuperAdmin();
    $contact = salesCustomer();

    $quotation = app(QuotationWorkflowService::class)->create($user, [
        'reference_number' => 'QT-CHAIN',
        'contact_id' => $contact->id,
        'quotation_date' => now()->toDateString(),
    ], salesLines(1, 100, 0));
    $quotation = app(QuotationWorkflowService::class)->send($quotation, $user);
    $quotation = app(QuotationWorkflowService::class)->approve($quotation, $user);
    $order = app(QuotationWorkflowService::class)->convertToSaleOrder($quotation, $user);
    $order = app(SaleOrderWorkflowService::class)->confirm($order, $user);
    $invoice = app(SaleOrderWorkflowService::class)->createInvoice($order, $user);
    $invoice = app(InvoiceWorkflowService::class)->issue($invoice, $user);

    Livewire::test('pages::sales.quotations-show', ['quotation' => $quotation->fresh()])
        ->assertOk()
        ->assertSee($order->reference_number);

    Livewire::test('pages::sales.sale-orders-show', ['saleOrder' => $order->fresh()])
        ->assertOk()
        ->assertSee($quotation->reference_number)
        ->assertSee($invoice->reference_number);

    Livewire::test('pages::sales.invoices-show', ['invoice' => $invoice->fresh()])
        ->assertOk()
        ->assertSee($order->reference_number);
});

test('invalid quotation transition is rejected and rolls back', function () {
    $user = actingAsSuperAdmin();
    $quotes = app(QuotationWorkflowService::class);
    $quotation = $quotes->create($user, [
        'reference_number' => 'QT-BAD-TR',
        'contact_id' => salesCustomer()->id,
        'quotation_date' => now()->toDateString(),
    ], salesLines());

    expect(fn () => $quotes->approve($quotation, $user))->toThrow(ValidationException::class);
    expect($quotation->fresh()->status)->toBe(QuotationStatus::Draft);
});
