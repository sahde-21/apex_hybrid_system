<?php

use App\Http\Controllers\BiExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:analytics.read'])->prefix('analytics')->name('analytics.')->group(function () {
    Route::livewire('/', 'pages::analytics.executive-hub')->name('hub');
    Route::livewire('reports', 'pages::analytics.reports-index')->name('reports');

    Route::middleware('throttle:exports')->group(function () {
        Route::get('export/{type}/csv', [BiExportController::class, 'csv'])->name('export.csv');
        Route::get('export/{type}/excel', [BiExportController::class, 'excel'])->name('export.excel');
        Route::get('export/{type}/pdf', [BiExportController::class, 'pdf'])->name('export.pdf');
        Route::get('export/{type}/print', [BiExportController::class, 'print'])->name('export.print');
    });
});
