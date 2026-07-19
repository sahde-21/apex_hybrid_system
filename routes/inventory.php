<?php

use App\Http\Controllers\PriceListController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VariantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('inventory')->group(function () {
    Route::middleware(['can:products.read'])->name('products.')->group(function () {
        Route::livewire('products', 'pages::inventory.products-index')->name('index');
        Route::livewire('products/create', 'pages::inventory.products-create')->middleware('can:products.create')->name('create');
        Route::livewire('products/{product}/edit', 'pages::inventory.products-edit')->middleware('can:products.update')->name('edit');

        Route::post('products', [ProductController::class, 'store'])->middleware('can:products.create')->name('store');
        Route::put('products/{product}', [ProductController::class, 'update'])->middleware('can:products.update')->name('update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('can:products.delete')->name('destroy');
    });

    Route::middleware(['can:variants.read'])->name('variants.')->group(function () {
        Route::livewire('variants', 'pages::inventory.variants-index')->name('index');
        Route::livewire('variants/create', 'pages::inventory.variants-create')->middleware('can:variants.create')->name('create');
        Route::livewire('variants/{variant}/edit', 'pages::inventory.variants-edit')->middleware('can:variants.update')->name('edit');

        Route::post('variants', [VariantController::class, 'store'])->middleware('can:variants.create')->name('store');
        Route::put('variants/{variant}', [VariantController::class, 'update'])->middleware('can:variants.update')->name('update');
        Route::delete('variants/{variant}', [VariantController::class, 'destroy'])->middleware('can:variants.delete')->name('destroy');
    });

    Route::middleware(['can:price-lists.read'])->name('price-lists.')->group(function () {
        Route::livewire('price-lists', 'pages::inventory.price-lists-index')->name('index');
        Route::livewire('price-lists/create', 'pages::inventory.price-lists-create')->middleware('can:price-lists.create')->name('create');
        Route::livewire('price-lists/{priceList}/edit', 'pages::inventory.price-lists-edit')->middleware('can:price-lists.update')->name('edit');

        Route::post('price-lists', [PriceListController::class, 'store'])->middleware('can:price-lists.create')->name('store');
        Route::put('price-lists/{priceList}', [PriceListController::class, 'update'])->middleware('can:price-lists.update')->name('update');
        Route::delete('price-lists/{priceList}', [PriceListController::class, 'destroy'])->middleware('can:price-lists.delete')->name('destroy');
    });
});
