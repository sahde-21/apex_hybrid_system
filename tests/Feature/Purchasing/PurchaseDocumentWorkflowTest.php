<?php

use App\Enums\BillStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\RfqStatus;
use App\Models\AccountingPosting;
use App\Models\Bill;
use App\Models\Contact;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Rfq;
use App\Models\SalesDocumentEvent;
use App\Services\Purchasing\BillWorkflowService;
use App\Services\Purchasing\PurchaseOrderWorkflowService;
use App\Services\Purchasing\PurchaseRequestWorkflowService;
use App\Services\Purchasing\RfqWorkflowService;
use App\Services\Sales\PaymentWorkflowService;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    test()->seed(RolePermissionSeeder::class);
    test()->seed(AccountingSeeder::class);
});

function purchaseLines(float $qty = 2, float $price = 50, float $tax = 10): array
{
    return [[
        'product_id' => null,
        'description' => 'Raw material',
        'quantity' => $qty,
        'unit_price' => $price,
        'discount_amount' => 0,
        'tax_amount' => $tax,
    ]];
}

function purchaseVendor(): Contact
{
    return Contact::factory()->supplier()->create();
}

test('purchase workflow localization key parity', function () {
    $en = require lang_path('en/scf.php');
    $ar = require lang_path('ar/scf.php');
    $ckb = require lang_path('ckb/scf.php');

    $enKeys = array_keys($en['purchase_workflow']);
    expect(array_keys($ar['purchase_workflow']))->toEqual($enKeys)
        ->and(array_keys($ckb['purchase_workflow']))->toEqual($enKeys);
});

test('purchase request submit approve reject convert and authorization', function () {
    $user = actingAsSuperAdmin();
    $prs = app(PurchaseRequestWorkflowService::class);

    $pr = $prs->create($user, [
        'reference_number' => 'PR-B2-001',
        'request_date' => now()->toDateString(),
        'department' => 'Operations',
        'needed_by' => now()->addWeek()->toDateString(),
    ], purchaseLines());

    expect($pr->status)->toBe(PurchaseRequestStatus::Draft)
        ->and($pr->lines)->toHaveCount(1);

    $this->get(route('purchase-requests.show', $pr))->assertOk();
    $this->get(route('purchase-requests.index'))->assertOk();

    $pr = $prs->submit($pr, $user);
    expect($pr->status)->toBe(PurchaseRequestStatus::Submitted);

    $pr = $prs->approve($pr, $user, 'OK');
    expect($pr->status)->toBe(PurchaseRequestStatus::Approved);

    $rfq = $prs->convertToRfq($pr, $user);
    expect($rfq)->toBeInstanceOf(Rfq::class)
        ->and($pr->fresh()->status)->toBe(PurchaseRequestStatus::Converted)
        ->and($rfq->purchase_request_id)->toBe($pr->id);

    $vendorA = purchaseVendor();
    $vendorB = purchaseVendor();
    app(RfqWorkflowService::class)->update($rfq, $user, [
        'reference_number' => $rfq->reference_number,
        'rfq_date' => $rfq->rfq_date->toDateString(),
    ], $rfq->lines->map(fn ($l) => [
        'product_id' => $l->product_id,
        'description' => $l->description,
        'quantity' => $l->quantity,
        'unit_price' => $l->unit_price,
        'discount_amount' => $l->discount_amount,
        'tax_amount' => $l->tax_amount,
    ])->all(), [$vendorA->id, $vendorB->id]);

    expect($rfq->fresh()->vendors)->toHaveCount(2);

    expect(fn () => $prs->convertToRfq($pr->fresh(), $user))
        ->toThrow(ValidationException::class);

    $rejected = $prs->create($user, [
        'reference_number' => 'PR-B2-REJ',
        'request_date' => now()->toDateString(),
    ], purchaseLines());
    $rejected = $prs->submit($rejected, $user);
    $rejected = $prs->reject($rejected, $user, 'Budget');
    expect($rejected->status)->toBe(PurchaseRequestStatus::Rejected);

    $cancelled = $prs->create($user, [
        'reference_number' => 'PR-B2-CAN',
        'request_date' => now()->toDateString(),
    ], purchaseLines());
    expect($prs->cancel($cancelled, $user)->status)->toBe(PurchaseRequestStatus::Cancelled);

    expect(SalesDocumentEvent::query()->where('document_type', $pr->getMorphClass())->count())->toBeGreaterThan(0);

    actingAsUserWithPermissions(['purchase-requests.read']);
    expect(fn () => $prs->submit(PurchaseRequest::factory()->create(), auth()->user()))
        ->toThrow(HttpException::class);
});

test('rfq send accept convert duplicate conversion guard', function () {
    $user = actingAsSuperAdmin();
    $vendor = purchaseVendor();
    $rfqs = app(RfqWorkflowService::class);

    $rfq = $rfqs->create($user, [
        'reference_number' => 'RFQ-B2-001',
        'rfq_date' => now()->toDateString(),
        'valid_until' => now()->addDays(10)->toDateString(),
        'vendor_ids' => [$vendor->id, purchaseVendor()->id],
    ], purchaseLines(1, 100, 0));

    expect($rfq->status)->toBe(RfqStatus::Draft);
    $this->get(route('rfqs.show', $rfq))->assertOk();

    $rfq = $rfqs->send($rfq, $user);
    expect($rfq->status)->toBe(RfqStatus::Sent);

    $rfq = $rfqs->recordVendorResponse($rfq, $user, $vendor->id, [
        'quoted_total' => 100,
        'quoted_tax' => 0,
        'notes' => 'Best price',
    ]);
    expect($rfq->status)->toBe(RfqStatus::VendorResponse);

    $rfq = $rfqs->accept($rfq, $user, $vendor->id);
    expect($rfq->status)->toBe(RfqStatus::Accepted)
        ->and($rfq->selected_vendor_id)->toBe($vendor->id);

    $po = $rfqs->convertToPurchaseOrder($rfq, $user);
    expect($po)->toBeInstanceOf(PurchaseOrder::class)
        ->and($rfq->fresh()->status)->toBe(RfqStatus::Converted)
        ->and($po->rfq_id)->toBe($rfq->id)
        ->and($po->contact_id)->toBe($vendor->id)
        ->and($po->lines)->toHaveCount(1);

    expect(fn () => $rfqs->convertToPurchaseOrder($rfq->fresh(), $user))
        ->toThrow(ValidationException::class);

    $dup = $rfqs->duplicate($rfq, $user);
    expect($dup->status)->toBe(RfqStatus::Draft);
});

test('purchase order submit confirm bill partial over-bill guard', function () {
    $user = actingAsSuperAdmin();
    $vendor = purchaseVendor();
    $orders = app(PurchaseOrderWorkflowService::class);

    $order = $orders->create($user, [
        'reference_number' => 'PO-B2-001',
        'contact_id' => $vendor->id,
        'order_date' => now()->toDateString(),
        'expected_date' => now()->addWeek()->toDateString(),
    ], purchaseLines(10, 20, 0));

    expect($order->status)->toBe(PurchaseOrderStatus::Draft);
    $this->get(route('purchase-orders.show', $order))->assertOk();

    $order = $orders->submit($order, $user);
    $order = $orders->approve($order, $user);
    $order = $orders->confirm($order, $user);
    expect($order->status)->toBe(PurchaseOrderStatus::Confirmed);

    $line = $order->lines()->first();
    $partial = $orders->createBill($order, $user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 4],
    ]);
    expect($partial->status)->toBe(BillStatus::Draft)
        ->and((float) $partial->lines()->first()->quantity)->toBe(4.0)
        ->and($order->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyBilled);

    expect(fn () => $orders->createBill($order->fresh(), $user, [
        ['purchase_order_line_id' => $line->id, 'quantity' => 99],
    ]))->toThrow(ValidationException::class);

    $orders->createBill($order->fresh(), $user);
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::FullyBilled)
        ->and((float) $line->fresh()->quantity_billed)->toBe(10.0);

    actingAsUserWithPermissions(['purchase-orders.read']);
    expect(fn () => $orders->confirm(PurchaseOrder::factory()->create(['contact_id' => $vendor->id]), auth()->user()))
        ->toThrow(HttpException::class);
});

test('vendor bill issue posts accounting void payment settlement and overpayment', function () {
    $user = actingAsSuperAdmin();
    $vendor = purchaseVendor();
    $bills = app(BillWorkflowService::class);
    $payments = app(PaymentWorkflowService::class);

    $bill = $bills->create($user, [
        'reference_number' => 'BILL-B2-001',
        'contact_id' => $vendor->id,
        'bill_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
    ], purchaseLines(1, 100, 10));

    expect($bill->status)->toBe(BillStatus::Draft);
    $this->get(route('bills.show', $bill))->assertOk();

    $bill = $bills->issue($bill->fresh(), $user);
    expect($bill->status)->toBe(BillStatus::Received)
        ->and($bill->issued_at)->not->toBeNull();

    $posting = AccountingPosting::query()
        ->where('source_type', $bill::class)
        ->where('source_id', $bill->id)
        ->where('event', 'bill.posted')
        ->first();
    expect($posting)->not->toBeNull()
        ->and($posting->journal_entry_id)->not->toBeNull();

    expect(fn () => $bills->update($bill->fresh(), $user, [
        'reference_number' => 'BILL-B2-001',
        'contact_id' => $vendor->id,
        'bill_date' => now()->toDateString(),
    ], purchaseLines()))->toThrow(ValidationException::class);

    $partial = $payments->create($user, [
        'reference_number' => 'VPAY-B2-P1',
        'contact_id' => $vendor->id,
        'bill_id' => $bill->id,
        'payment_date' => now()->toDateString(),
        'amount' => 50,
        'type' => PaymentType::Outgoing->value,
        'payment_method' => 'cash',
    ]);
    expect($partial->status)->toBe(PaymentStatus::Draft);

    $partial = $payments->post($partial, $user);
    expect($partial->status)->toBe(PaymentStatus::Posted)
        ->and($bill->fresh()->status)->toBe(BillStatus::PartiallyPaid)
        ->and((float) $bill->fresh()->paid_amount)->toBe(50.0);

    expect(fn () => $payments->create($user, [
        'reference_number' => 'VPAY-B2-OVER',
        'contact_id' => $vendor->id,
        'bill_id' => $bill->id,
        'payment_date' => now()->toDateString(),
        'amount' => 9999,
        'type' => PaymentType::Outgoing->value,
    ]))->toThrow(ValidationException::class);

    $full = $payments->create($user, [
        'reference_number' => 'VPAY-B2-FULL',
        'contact_id' => $vendor->id,
        'bill_id' => $bill->id,
        'payment_date' => now()->toDateString(),
        'amount' => 60,
        'type' => PaymentType::Outgoing->value,
        'payment_method' => 'bank_transfer',
    ]);
    $full = $payments->post($full, $user);
    expect($bill->fresh()->status)->toBe(BillStatus::Paid);

    $payments->reverse($full, $user, 'Dispute');
    expect($full->fresh()->status)->toBe(PaymentStatus::Reversed)
        ->and($bill->fresh()->status)->toBe(BillStatus::PartiallyPaid);

    $voidable = $bills->create($user, [
        'reference_number' => 'BILL-B2-VOID',
        'contact_id' => $vendor->id,
        'bill_date' => now()->toDateString(),
    ], purchaseLines(1, 25, 0));
    $voidable = $bills->issue($voidable, $user);
    expect($bills->void($voidable, $user, 'Error')->status)->toBe(BillStatus::Void);

    $draftCancel = $bills->create($user, [
        'reference_number' => 'BILL-B2-DC',
        'contact_id' => $vendor->id,
        'bill_date' => now()->toDateString(),
    ], purchaseLines(1, 15, 0));
    expect($bills->cancel($draftCancel, $user)->status)->toBe(BillStatus::Cancelled);

    actingAsUserWithPermissions(['bills.read']);
    expect(fn () => $bills->issue(Bill::factory()->create([
        'contact_id' => $vendor->id,
        'total_amount' => 10,
    ]), auth()->user()))->toThrow(HttpException::class);
});

test('document relationships across purchase chain', function () {
    $user = actingAsSuperAdmin();
    $vendor = purchaseVendor();

    $pr = app(PurchaseRequestWorkflowService::class)->create($user, [
        'reference_number' => 'PR-CHAIN',
        'request_date' => now()->toDateString(),
    ], purchaseLines(1, 80, 0));
    $pr = app(PurchaseRequestWorkflowService::class)->submit($pr, $user);
    $pr = app(PurchaseRequestWorkflowService::class)->approve($pr, $user);
    $rfq = app(PurchaseRequestWorkflowService::class)->convertToRfq($pr, $user);
    app(RfqWorkflowService::class)->update($rfq, $user, [
        'reference_number' => $rfq->reference_number,
        'rfq_date' => $rfq->rfq_date->toDateString(),
    ], $rfq->lines->map(fn ($l) => [
        'product_id' => $l->product_id,
        'description' => $l->description,
        'quantity' => $l->quantity,
        'unit_price' => $l->unit_price,
        'discount_amount' => $l->discount_amount,
        'tax_amount' => $l->tax_amount,
    ])->all(), [$vendor->id]);
    $rfq = app(RfqWorkflowService::class)->send($rfq->fresh(), $user);
    $rfq = app(RfqWorkflowService::class)->accept($rfq, $user, $vendor->id);
    $po = app(RfqWorkflowService::class)->convertToPurchaseOrder($rfq, $user);
    $po = app(PurchaseOrderWorkflowService::class)->confirm($po, $user);
    $bill = app(PurchaseOrderWorkflowService::class)->createBill($po, $user);
    $bill = app(BillWorkflowService::class)->issue($bill, $user);

    Livewire::test('pages::purchasing.purchase-requests-show', ['purchaseRequest' => $pr->fresh()])
        ->assertOk()
        ->assertSee($rfq->reference_number);

    Livewire::test('pages::purchasing.rfqs-show', ['rfq' => $rfq->fresh()])
        ->assertOk()
        ->assertSee($po->reference_number);

    Livewire::test('pages::purchasing.purchase-orders-show', ['purchaseOrder' => $po->fresh()])
        ->assertOk()
        ->assertSee($bill->reference_number);

    Livewire::test('pages::purchasing.bills-show', ['bill' => $bill->fresh()])
        ->assertOk()
        ->assertSee($po->reference_number);
});

test('invalid purchase request transition rolls back', function () {
    $user = actingAsSuperAdmin();
    $prs = app(PurchaseRequestWorkflowService::class);
    $pr = $prs->create($user, [
        'reference_number' => 'PR-BAD',
        'request_date' => now()->toDateString(),
    ], purchaseLines());

    expect(fn () => $prs->approve($pr, $user))->toThrow(ValidationException::class);
    expect($pr->fresh()->status)->toBe(PurchaseRequestStatus::Draft);
});
