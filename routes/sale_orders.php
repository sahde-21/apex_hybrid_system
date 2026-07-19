<?php

use App\Http\Controllers\SaleOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('sales')->group(function () {
    Route::middleware(['can:sale-orders.read'])->name('sale-orders.')->group(function () {
        Route::livewire('sale-orders', 'pages::sales.sale-orders-index')->name('index');
        Route::livewire('sale-orders/create', 'pages::sales.sale-orders-create')->middleware('can:sale-orders.create')->name('create');
        Route::livewire('sale-orders/{saleOrder}/edit', 'pages::sales.sale-orders-edit')->middleware('can:sale-orders.update')->name('edit');

        Route::post('sale-orders', [SaleOrderController::class, 'store'])->middleware('can:sale-orders.create')->name('store');
        Route::put('sale-orders/{saleOrder}', [SaleOrderController::class, 'update'])->middleware('can:sale-orders.update')->name('update');
        Route::delete('sale-orders/{saleOrder}', [SaleOrderController::class, 'destroy'])->middleware('can:sale-orders.delete')->name('destroy');
    });
});
