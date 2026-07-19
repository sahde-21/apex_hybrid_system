<?php

use App\Http\Controllers\BillOfMaterialController;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\QualityControlController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('manufacturing')->group(function () {

    Route::middleware(['can:production-orders.read'])->name('production-orders.')->group(function () {
        Route::livewire('production-orders', 'pages::manufacturing.production-orders-index')->name('index');
        Route::livewire('production-orders/create', 'pages::manufacturing.production-orders-create')->middleware('can:production-orders.create')->name('create');
        Route::livewire('production-orders/{productionOrder}/edit', 'pages::manufacturing.production-orders-edit')->middleware('can:production-orders.update')->name('edit');

        Route::post('production-orders', [ProductionOrderController::class, 'store'])->middleware('can:production-orders.create')->name('store');
        Route::put('production-orders/{productionOrder}', [ProductionOrderController::class, 'update'])->middleware('can:production-orders.update')->name('update');
        Route::delete('production-orders/{productionOrder}', [ProductionOrderController::class, 'destroy'])->middleware('can:production-orders.delete')->name('destroy');
    });

    Route::middleware(['can:bill-of-materials.read'])->name('bill-of-materials.')->group(function () {
        Route::livewire('bill-of-materials', 'pages::manufacturing.bill-of-materials-index')->name('index');
        Route::livewire('bill-of-materials/create', 'pages::manufacturing.bill-of-materials-create')->middleware('can:bill-of-materials.create')->name('create');
        Route::livewire('bill-of-materials/{billOfMaterial}/edit', 'pages::manufacturing.bill-of-materials-edit')->middleware('can:bill-of-materials.update')->name('edit');

        Route::post('bill-of-materials', [BillOfMaterialController::class, 'store'])->middleware('can:bill-of-materials.create')->name('store');
        Route::put('bill-of-materials/{billOfMaterial}', [BillOfMaterialController::class, 'update'])->middleware('can:bill-of-materials.update')->name('update');
        Route::delete('bill-of-materials/{billOfMaterial}', [BillOfMaterialController::class, 'destroy'])->middleware('can:bill-of-materials.delete')->name('destroy');
    });

    Route::middleware(['can:quality-control.read'])->name('quality-controls.')->group(function () {
        Route::livewire('quality-controls', 'pages::manufacturing.quality-controls-index')->name('index');
        Route::livewire('quality-controls/create', 'pages::manufacturing.quality-controls-create')->middleware('can:quality-control.create')->name('create');
        Route::livewire('quality-controls/{qualityControl}/edit', 'pages::manufacturing.quality-controls-edit')->middleware('can:quality-control.update')->name('edit');

        Route::post('quality-controls', [QualityControlController::class, 'store'])->middleware('can:quality-control.create')->name('store');
        Route::put('quality-controls/{qualityControl}', [QualityControlController::class, 'update'])->middleware('can:quality-control.update')->name('update');
        Route::delete('quality-controls/{qualityControl}', [QualityControlController::class, 'destroy'])->middleware('can:quality-control.delete')->name('destroy');
    });
});
