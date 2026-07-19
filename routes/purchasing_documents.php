<?php

use App\Http\Controllers\BillController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('purchasing')->group(function () {
    Route::middleware(['can:bills.read'])->name('bills.')->group(function () {
        Route::livewire('bills', 'pages::purchasing.bills-index')->name('index');
        Route::livewire('bills/create', 'pages::purchasing.bills-create')->middleware('can:bills.create')->name('create');
        Route::livewire('bills/{bill}/edit', 'pages::purchasing.bills-edit')->middleware('can:bills.update')->name('edit');

        Route::post('bills', [BillController::class, 'store'])->middleware('can:bills.create')->name('store');
        Route::put('bills/{bill}', [BillController::class, 'update'])->middleware('can:bills.update')->name('update');
        Route::delete('bills/{bill}', [BillController::class, 'destroy'])->middleware('can:bills.delete')->name('destroy');
    });
});
