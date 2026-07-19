<?php

use App\Http\Controllers\InventoryAdjustmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('inventory')->group(function () {
    Route::middleware(['can:inventory-adjustments.read'])->name('inventory-adjustments.')->group(function () {
        Route::livewire('inventory-adjustments', 'pages::inventory.inventory-adjustments-index')->name('index');
        Route::livewire('inventory-adjustments/create', 'pages::inventory.inventory-adjustments-create')->middleware('can:inventory-adjustments.create')->name('create');
        Route::livewire('inventory-adjustments/{inventoryAdjustment}/edit', 'pages::inventory.inventory-adjustments-edit')->middleware('can:inventory-adjustments.update')->name('edit');

        Route::post('inventory-adjustments', [InventoryAdjustmentController::class, 'store'])->middleware('can:inventory-adjustments.create')->name('store');
        Route::put('inventory-adjustments/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'update'])->middleware('can:inventory-adjustments.update')->name('update');
        Route::delete('inventory-adjustments/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'destroy'])->middleware('can:inventory-adjustments.delete')->name('destroy');
    });
});
