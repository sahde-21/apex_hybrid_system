<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\BillController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DocumentationController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\PurchaseRequestController;
use App\Http\Controllers\Api\V1\QuotationController;
use App\Http\Controllers\Api\V1\RfqController;
use App\Http\Controllers\Api\V1\SaleOrderController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\VendorPaymentController;
use App\Models\Contact;
use App\Enums\ContactType;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)
    ->middleware('throttle:60,1')
    ->name('health');

Route::get('/docs', DocumentationController::class)
    ->middleware(array_filter([
        'throttle:30,1',
        app()->isProduction() ? 'auth:sanctum' : null,
    ]))
    ->name('docs');

Route::middleware('throttle:api-auth')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
});

Route::middleware(['auth:sanctum', 'api.active', 'throttle:api', 'api.idempotent'])->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');

    Route::get('/tokens', [TokenController::class, 'index'])->name('tokens.index');
    Route::post('/tokens', [TokenController::class, 'store'])->name('tokens.store');
    Route::delete('/tokens/others', [TokenController::class, 'destroyOthers'])->name('tokens.destroy-others');
    Route::get('/tokens/{token}', [TokenController::class, 'show'])->whereNumber('token')->name('tokens.show');
    Route::delete('/tokens/{token}', [TokenController::class, 'destroy'])->whereNumber('token')->name('tokens.destroy');

    Route::apiResource('products', ProductController::class);

    Route::bind('customer', fn (string $value) => Contact::query()
        ->whereKey($value)
        ->whereIn('type', [ContactType::Customer, ContactType::Both])
        ->firstOrFail());

    Route::bind('supplier', fn (string $value) => Contact::query()
        ->whereKey($value)
        ->whereIn('type', [ContactType::Supplier, ContactType::Both])
        ->firstOrFail());

    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('suppliers', SupplierController::class);

    Route::apiResource('quotations', QuotationController::class);
    Route::post('quotations/{quotation}/send', [QuotationController::class, 'send'])->name('quotations.send');
    Route::post('quotations/{quotation}/accept', [QuotationController::class, 'accept'])->name('quotations.accept');
    Route::post('quotations/{quotation}/reject', [QuotationController::class, 'reject'])->name('quotations.reject');
    Route::post('quotations/{quotation}/expire', [QuotationController::class, 'expire'])->name('quotations.expire');
    Route::post('quotations/{quotation}/cancel', [QuotationController::class, 'cancel'])->name('quotations.cancel');
    Route::post('quotations/{quotation}/duplicate', [QuotationController::class, 'duplicate'])->name('quotations.duplicate');
    Route::post('quotations/{quotation}/convert-to-sale-order', [QuotationController::class, 'convertToSaleOrder'])
        ->name('quotations.convert');

    Route::apiResource('sale-orders', SaleOrderController::class);
    Route::post('sale-orders/{sale_order}/submit', [SaleOrderController::class, 'submit'])->name('sale-orders.submit');
    Route::post('sale-orders/{sale_order}/approve', [SaleOrderController::class, 'approve'])->name('sale-orders.approve');
    Route::post('sale-orders/{sale_order}/reject', [SaleOrderController::class, 'reject'])->name('sale-orders.reject');
    Route::post('sale-orders/{sale_order}/confirm', [SaleOrderController::class, 'confirm'])->name('sale-orders.confirm');
    Route::post('sale-orders/{sale_order}/cancel', [SaleOrderController::class, 'cancel'])->name('sale-orders.cancel');
    Route::post('sale-orders/{sale_order}/create-invoice', [SaleOrderController::class, 'createInvoice'])
        ->middleware('api.idempotent')
        ->name('sale-orders.invoice');

    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])
        ->middleware(['throttle:api-posting', 'api.idempotent'])
        ->name('invoices.issue');
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
    Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');

    Route::apiResource('payments', PaymentController::class);
    Route::post('payments/{payment}/post', [PaymentController::class, 'post'])
        ->middleware(['throttle:api-posting', 'api.idempotent'])
        ->name('payments.post');
    Route::post('payments/{payment}/reverse', [PaymentController::class, 'reverse'])->name('payments.reverse');
    Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');

    Route::apiResource('purchase-requests', PurchaseRequestController::class);
    Route::post('purchase-requests/{purchase_request}/submit', [PurchaseRequestController::class, 'submit'])->name('purchase-requests.submit');
    Route::post('purchase-requests/{purchase_request}/approve', [PurchaseRequestController::class, 'approve'])->name('purchase-requests.approve');
    Route::post('purchase-requests/{purchase_request}/reject', [PurchaseRequestController::class, 'reject'])->name('purchase-requests.reject');
    Route::post('purchase-requests/{purchase_request}/cancel', [PurchaseRequestController::class, 'cancel'])->name('purchase-requests.cancel');
    Route::post('purchase-requests/{purchase_request}/convert-to-rfq', [PurchaseRequestController::class, 'convertToRfq'])
        ->middleware('api.idempotent')
        ->name('purchase-requests.convert');

    Route::apiResource('rfqs', RfqController::class);
    Route::post('rfqs/{rfq}/send', [RfqController::class, 'send'])->name('rfqs.send');
    Route::post('rfqs/{rfq}/accept', [RfqController::class, 'accept'])->name('rfqs.accept');
    Route::post('rfqs/{rfq}/reject', [RfqController::class, 'reject'])->name('rfqs.reject');
    Route::post('rfqs/{rfq}/expire', [RfqController::class, 'expire'])->name('rfqs.expire');
    Route::post('rfqs/{rfq}/cancel', [RfqController::class, 'cancel'])->name('rfqs.cancel');
    Route::post('rfqs/{rfq}/duplicate', [RfqController::class, 'duplicate'])->name('rfqs.duplicate');
    Route::post('rfqs/{rfq}/convert-to-purchase-order', [RfqController::class, 'convertToPurchaseOrder'])
        ->middleware('api.idempotent')
        ->name('rfqs.convert');

    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchase_order}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchase_order}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('purchase-orders/{purchase_order}/confirm', [PurchaseOrderController::class, 'confirm'])->name('purchase-orders.confirm');
    Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('purchase-orders/{purchase_order}/create-bill', [PurchaseOrderController::class, 'createBill'])
        ->middleware('api.idempotent')
        ->name('purchase-orders.bill');

    Route::apiResource('bills', BillController::class);
    Route::post('bills/{bill}/issue', [BillController::class, 'issue'])
        ->middleware(['throttle:api-posting', 'api.idempotent'])
        ->name('bills.issue');
    Route::post('bills/{bill}/void', [BillController::class, 'void'])->name('bills.void');
    Route::post('bills/{bill}/cancel', [BillController::class, 'cancel'])->name('bills.cancel');

    Route::get('vendor-payments', [VendorPaymentController::class, 'index'])->name('vendor-payments.index');
    Route::post('vendor-payments', [VendorPaymentController::class, 'store'])
        ->middleware('api.idempotent')
        ->name('vendor-payments.store');
    Route::get('vendor-payments/{payment}', [VendorPaymentController::class, 'show'])->name('vendor-payments.show');
    Route::put('vendor-payments/{payment}', [VendorPaymentController::class, 'update'])->name('vendor-payments.update');
    Route::patch('vendor-payments/{payment}', [VendorPaymentController::class, 'update']);
    Route::delete('vendor-payments/{payment}', [VendorPaymentController::class, 'destroy'])->name('vendor-payments.destroy');
    Route::post('vendor-payments/{payment}/post', [VendorPaymentController::class, 'post'])
        ->middleware(['throttle:api-posting', 'api.idempotent'])
        ->name('vendor-payments.post');
    Route::post('vendor-payments/{payment}/reverse', [VendorPaymentController::class, 'reverse'])->name('vendor-payments.reverse');
    Route::post('vendor-payments/{payment}/cancel', [VendorPaymentController::class, 'cancel'])->name('vendor-payments.cancel');
});
