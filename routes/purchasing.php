<?php

use App\Http\Controllers\SupplierEvaluationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('purchasing')->group(function () {

    Route::middleware(['can:supplier-evaluations.read'])->name('supplier-evaluations.')->group(function () {
        Route::livewire('supplier-evaluations', 'pages::purchasing.supplier-evaluations-index')->name('index');
        Route::livewire('supplier-evaluations/create', 'pages::purchasing.supplier-evaluations-create')->middleware('can:supplier-evaluations.create')->name('create');
        Route::livewire('supplier-evaluations/{supplierEvaluation}/edit', 'pages::purchasing.supplier-evaluations-edit')->middleware('can:supplier-evaluations.update')->name('edit');

        Route::post('supplier-evaluations', [SupplierEvaluationController::class, 'store'])->middleware('can:supplier-evaluations.create')->name('store');
        Route::put('supplier-evaluations/{supplierEvaluation}', [SupplierEvaluationController::class, 'update'])->middleware('can:supplier-evaluations.update')->name('update');
        Route::delete('supplier-evaluations/{supplierEvaluation}', [SupplierEvaluationController::class, 'destroy'])->middleware('can:supplier-evaluations.delete')->name('destroy');
    });
});
