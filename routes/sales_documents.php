<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuotationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('sales')->group(function () {
    Route::middleware(['can:quotations.read'])->name('quotations.')->group(function () {
        Route::livewire('quotations', 'pages::sales.quotations-index')->name('index');
        Route::livewire('quotations/create', 'pages::sales.quotations-create')->middleware('can:quotations.create')->name('create');
        Route::livewire('quotations/{quotation}', 'pages::sales.quotations-show')->name('show');
        Route::livewire('quotations/{quotation}/edit', 'pages::sales.quotations-edit')->middleware('can:quotations.update')->name('edit');

        Route::post('quotations', [QuotationController::class, 'store'])->middleware('can:quotations.create')->name('store');
        Route::put('quotations/{quotation}', [QuotationController::class, 'update'])->middleware('can:quotations.update')->name('update');
        Route::delete('quotations/{quotation}', [QuotationController::class, 'destroy'])->middleware('can:quotations.delete')->name('destroy');
    });

    Route::middleware(['can:invoices.read'])->name('invoices.')->group(function () {
        Route::livewire('invoices', 'pages::sales.invoices-index')->name('index');
        Route::livewire('invoices/create', 'pages::sales.invoices-create')->middleware('can:invoices.create')->name('create');
        Route::livewire('invoices/{invoice}', 'pages::sales.invoices-show')->name('show');
        Route::livewire('invoices/{invoice}/edit', 'pages::sales.invoices-edit')->middleware('can:invoices.update')->name('edit');

        Route::post('invoices', [InvoiceController::class, 'store'])->middleware('can:invoices.create')->name('store');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->middleware('can:invoices.update')->name('update');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->middleware('can:invoices.delete')->name('destroy');
    });
});
