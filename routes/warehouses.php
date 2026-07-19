<?php

use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('inventory')->group(function () {
    Route::middleware(['can:warehouses.read'])->name('warehouses.')->group(function () {
        Route::livewire('warehouses', 'pages::inventory.warehouses-index')->name('index');
        Route::livewire('warehouses/create', 'pages::inventory.warehouses-create')->middleware('can:warehouses.create')->name('create');
        Route::livewire('warehouses/{warehouse}/edit', 'pages::inventory.warehouses-edit')->middleware('can:warehouses.update')->name('edit');

        Route::post('warehouses', [WarehouseController::class, 'store'])->middleware('can:warehouses.create')->name('store');
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->middleware('can:warehouses.update')->name('update');
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->middleware('can:warehouses.delete')->name('destroy');
    });
});
