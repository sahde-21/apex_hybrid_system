<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('purchasing')->group(function () {
    Route::middleware(['can:rfqs.read'])->name('rfqs.')->group(function () {
        Route::livewire('rfqs', 'pages::purchasing.rfqs-index')->name('index');
        Route::livewire('rfqs/create', 'pages::purchasing.rfqs-create')
            ->middleware('can:rfqs.create')->name('create');
        Route::livewire('rfqs/{rfq}', 'pages::purchasing.rfqs-show')->name('show');
        Route::livewire('rfqs/{rfq}/edit', 'pages::purchasing.rfqs-edit')
            ->middleware('can:rfqs.update')->name('edit');
    });
});
