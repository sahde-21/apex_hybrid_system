<?php

use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'throttle:prints'])->group(function () {
    Route::get('print/{type}/{id}', [PrintController::class, 'print'])
        ->whereIn('type', [
            'invoice',
            'payment',
            'sale-order',
            'purchase-order',
            'bill',
            'quotation',
            'expense',
            'pos-sale',
        ])
        ->whereNumber('id')
        ->name('print.document');
});
