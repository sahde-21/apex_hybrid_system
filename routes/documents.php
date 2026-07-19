<?php

use App\Http\Controllers\DocumentFileController;
use App\Http\Controllers\DocumentShareController;
use Illuminate\Support\Facades\Route;

Route::get('share/documents/{token}', [DocumentShareController::class, 'show'])->name('documents.share.show');
Route::post('share/documents/{token}/unlock', [DocumentShareController::class, 'unlock'])->name('documents.share.unlock');
Route::get('share/documents/{token}/download', [DocumentShareController::class, 'download'])->name('documents.share.download');

Route::middleware(['auth', 'verified'])->prefix('documents')->name('documents.')->group(function () {
    Route::middleware('can:documents.read')->group(function () {
        Route::livewire('/', 'pages::documents.center')->name('index');
        Route::livewire('recycle-bin', 'pages::documents.recycle-bin')->name('recycle-bin');

        Route::get('{managedDocument}/download', [DocumentFileController::class, 'download'])->name('download');
        Route::get('{managedDocument}/preview', [DocumentFileController::class, 'preview'])->name('preview');
        Route::get('{managedDocument}/print', [DocumentFileController::class, 'print'])->name('print');
    });
});
