<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('contacts')->group(function () {
    Route::middleware(['can:contacts.read'])->name('contacts.')->group(function () {
        Route::livewire('/', 'pages::contacts.contacts-index')->name('index');
        Route::livewire('create', 'pages::contacts.contacts-create')->middleware('can:contacts.create')->name('create');
        Route::livewire('{contact}/edit', 'pages::contacts.contacts-edit')->middleware('can:contacts.update')->name('edit');

        Route::post('/', [ContactController::class, 'store'])->middleware('can:contacts.create')->name('store');
        Route::put('{contact}', [ContactController::class, 'update'])->middleware('can:contacts.update')->name('update');
        Route::delete('{contact}', [ContactController::class, 'destroy'])->middleware('can:contacts.delete')->name('destroy');
    });
});
