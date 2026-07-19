<?php

use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'throttle:exports'])->group(function () {
    Route::get('export/{type}', ExportController::class)
        ->whereIn('type', ['products', 'contacts', 'invoices'])
        ->name('export.csv');

    Route::get('export/{type}/excel', [ExportController::class, 'excel'])
        ->whereIn('type', ['products', 'contacts', 'invoices'])
        ->name('export.excel');

    Route::get('export/{type}/{id}/pdf', [ExportController::class, 'pdf'])
        ->whereIn('type', ['invoice', 'invoices'])
        ->whereNumber('id')
        ->name('export.pdf');
});
