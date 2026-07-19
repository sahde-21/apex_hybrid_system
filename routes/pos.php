<?php

use App\Http\Controllers\PosRegisterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('pos')->name('pos.')->group(function () {
    Route::middleware(['can:pos.read'])->group(function () {
        Route::livewire('/', 'pages::pos.terminal')->middleware('can:pos.create')->name('terminal');
        Route::livewire('/sales', 'pages::pos.sales-index')->name('sales.index');
        Route::livewire('/shifts', 'pages::pos.shifts-index')->name('shifts.index');
        Route::livewire('/summary', 'pages::pos.daily-summary')->name('summary');
        Route::livewire('/registers', 'pages::pos.registers-index')->middleware('can:pos.update')->name('registers.index');

        Route::post('/registers', [PosRegisterController::class, 'store'])
            ->middleware('can:pos.create')
            ->name('registers.store');
        Route::put('/registers/{posRegister}', [PosRegisterController::class, 'update'])
            ->middleware('can:pos.update')
            ->name('registers.update');
        Route::delete('/registers/{posRegister}', [PosRegisterController::class, 'destroy'])
            ->middleware('can:pos.delete')
            ->name('registers.destroy');
    });
});
