<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('purchasing')->group(function () {
    Route::middleware(['can:purchase-requests.read'])->name('purchase-requests.')->group(function () {
        Route::livewire('purchase-requests', 'pages::purchasing.purchase-requests-index')->name('index');
        Route::livewire('purchase-requests/create', 'pages::purchasing.purchase-requests-create')
            ->middleware('can:purchase-requests.create')->name('create');
        Route::livewire('purchase-requests/{purchaseRequest}', 'pages::purchasing.purchase-requests-show')->name('show');
        Route::livewire('purchase-requests/{purchaseRequest}/edit', 'pages::purchasing.purchase-requests-edit')
            ->middleware('can:purchase-requests.update')->name('edit');
    });
});
