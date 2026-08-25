<?php

use App\Http\Controllers\DeliveryTripController;
use App\Http\Controllers\FloorPlanController;
use App\Http\Controllers\ShippingMethodController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\VehicleMaintenanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('warehouse')->group(function () {

    Route::middleware(['can:shipping-methods.read'])->name('shipping-methods.')->group(function () {
        Route::livewire('shipping-methods', 'pages::warehouse.shipping-methods-index')->name('index');
        Route::livewire('shipping-methods/create', 'pages::warehouse.shipping-methods-create')->middleware('can:shipping-methods.create')->name('create');
        Route::livewire('shipping-methods/{shippingMethod}/edit', 'pages::warehouse.shipping-methods-edit')->middleware('can:shipping-methods.update')->name('edit');

        Route::post('shipping-methods', [ShippingMethodController::class, 'store'])->middleware('can:shipping-methods.create')->name('store');
        Route::put('shipping-methods/{shippingMethod}', [ShippingMethodController::class, 'update'])->middleware('can:shipping-methods.update')->name('update');
        Route::delete('shipping-methods/{shippingMethod}', [ShippingMethodController::class, 'destroy'])->middleware('can:shipping-methods.delete')->name('destroy');
    });

    Route::middleware(['can:delivery-trips.read'])->name('delivery-trips.')->group(function () {
        Route::livewire('delivery-trips', 'pages::warehouse.delivery-trips-index')->name('index');
        Route::livewire('delivery-trips/create', 'pages::warehouse.delivery-trips-create')->middleware('can:delivery-trips.create')->name('create');
        Route::livewire('delivery-trips/{deliveryTrip}/edit', 'pages::warehouse.delivery-trips-edit')->middleware('can:delivery-trips.update')->name('edit');

        Route::post('delivery-trips', [DeliveryTripController::class, 'store'])->middleware('can:delivery-trips.create')->name('store');
        Route::put('delivery-trips/{deliveryTrip}', [DeliveryTripController::class, 'update'])->middleware('can:delivery-trips.update')->name('update');
        Route::delete('delivery-trips/{deliveryTrip}', [DeliveryTripController::class, 'destroy'])->middleware('can:delivery-trips.delete')->name('destroy');
    });

    Route::middleware(['can:vehicle-maintenance.read'])->name('vehicle-maintenance.')->group(function () {
        Route::livewire('vehicle-maintenance', 'pages::warehouse.vehicle-maintenance-index')->name('index');
        Route::livewire('vehicle-maintenance/create', 'pages::warehouse.vehicle-maintenance-create')->middleware('can:vehicle-maintenance.create')->name('create');
        Route::livewire('vehicle-maintenance/{vehicleMaintenance}/edit', 'pages::warehouse.vehicle-maintenance-edit')->middleware('can:vehicle-maintenance.update')->name('edit');

        Route::post('vehicle-maintenance', [VehicleMaintenanceController::class, 'store'])->middleware('can:vehicle-maintenance.create')->name('store');
        Route::put('vehicle-maintenance/{vehicleMaintenance}', [VehicleMaintenanceController::class, 'update'])->middleware('can:vehicle-maintenance.update')->name('update');
        Route::delete('vehicle-maintenance/{vehicleMaintenance}', [VehicleMaintenanceController::class, 'destroy'])->middleware('can:vehicle-maintenance.delete')->name('destroy');
    });

    Route::middleware(['can:floor-plans.read'])->name('floor-plans.')->group(function () {
        Route::livewire('floor-plans', 'pages::warehouse.floor-plans-index')->name('index');
        Route::livewire('floor-plans/create', 'pages::warehouse.floor-plans-create')->middleware('can:floor-plans.create')->name('create');
        Route::livewire('floor-plans/{floorPlan}/edit', 'pages::warehouse.floor-plans-edit')->middleware('can:floor-plans.update')->name('edit');

        Route::post('floor-plans', [FloorPlanController::class, 'store'])->middleware('can:floor-plans.create')->name('store');
        Route::put('floor-plans/{floorPlan}', [FloorPlanController::class, 'update'])->middleware('can:floor-plans.update')->name('update');
        Route::delete('floor-plans/{floorPlan}', [FloorPlanController::class, 'destroy'])->middleware('can:floor-plans.delete')->name('destroy');
    });

    Route::middleware(['can:stock-transfers.read'])->name('stock-transfers.')->group(function () {
        Route::livewire('stock-transfers', 'pages::warehouse.stock-transfers-index')->name('index');
        Route::livewire('stock-transfers/create', 'pages::warehouse.stock-transfers-create')->middleware('can:stock-transfers.create')->name('create');
        Route::livewire('stock-transfers/{stockTransfer}/edit', 'pages::warehouse.stock-transfers-edit')->middleware('can:stock-transfers.read')->name('edit');

        Route::post('stock-transfers', [StockTransferController::class, 'store'])->middleware('can:stock-transfers.create')->name('store');
        Route::put('stock-transfers/{stockTransfer}', [StockTransferController::class, 'update'])->middleware('can:stock-transfers.update')->name('update');
        Route::delete('stock-transfers/{stockTransfer}', [StockTransferController::class, 'destroy'])->middleware('can:stock-transfers.delete')->name('destroy');
    });
});
