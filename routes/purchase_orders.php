<?php

use App\Http\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('purchasing')->group(function () {
    Route::middleware(['can:purchase-orders.read'])->name('purchase-orders.')->group(function () {
        Route::livewire('purchase-orders', 'pages::purchasing.purchase-orders-index')->name('index');
        Route::livewire('purchase-orders/create', 'pages::purchasing.purchase-orders-create')->middleware('can:purchase-orders.create')->name('create');
        Route::livewire('purchase-orders/{purchaseOrder}', 'pages::purchasing.purchase-orders-show')->name('show');
        Route::livewire('purchase-orders/{purchaseOrder}/edit', 'pages::purchasing.purchase-orders-edit')->middleware('can:purchase-orders.update')->name('edit');

        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->middleware('can:purchase-orders.create')->name('store');
        Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->middleware('can:purchase-orders.update')->name('update');
        Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->middleware('can:purchase-orders.delete')->name('destroy');
    });
});
