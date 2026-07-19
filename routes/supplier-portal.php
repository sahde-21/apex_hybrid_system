<?php

use App\Http\Controllers\Supplier\Auth\SupplierAuthController;
use App\Http\Controllers\Supplier\SupplierDocumentController;
use App\Http\Middleware\EnsureSupplierEmailIsVerified;
use App\Http\Middleware\UseSupplierGuard;
use Illuminate\Support\Facades\Route;

Route::prefix('supplier')->name('supplier.')->middleware(UseSupplierGuard::class)->group(function () {
    Route::middleware('guest:supplier')->group(function () {
        Route::get('login', [SupplierAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [SupplierAuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login.store');

        Route::get('forgot-password', [SupplierAuthController::class, 'showForgotPassword'])->name('password.request');
        Route::post('forgot-password', [SupplierAuthController::class, 'sendResetLink'])
            ->middleware('throttle:login')
            ->name('password.email');

        Route::get('reset-password/{token}', [SupplierAuthController::class, 'showResetPassword'])->name('password.reset');
        Route::post('reset-password', [SupplierAuthController::class, 'resetPassword'])->name('password.update');

        Route::get('two-factor-challenge', [SupplierAuthController::class, 'showTwoFactorChallenge'])->name('two-factor.login');
        Route::post('two-factor-challenge', [SupplierAuthController::class, 'verifyTwoFactor'])
            ->middleware('throttle:login')
            ->name('two-factor.login.store');
    });

    Route::get('verify-email/{id}/{hash}', [SupplierAuthController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::middleware('auth:supplier')->group(function () {
        Route::post('logout', [SupplierAuthController::class, 'logout'])->name('logout');

        Route::get('verify-email', [SupplierAuthController::class, 'showVerifyNotice'])->name('verification.notice');
        Route::post('verify-email', [SupplierAuthController::class, 'sendVerification'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::middleware(EnsureSupplierEmailIsVerified::class)->group(function () {
            Route::livewire('/', 'pages::supplier.dashboard')->name('dashboard');

            Route::livewire('purchase-orders', 'pages::supplier.purchase-orders-index')->name('purchase-orders.index');
            Route::livewire('purchase-orders/{purchaseOrder}', 'pages::supplier.purchase-orders-show')->name('purchase-orders.show');

            Route::livewire('deliveries', 'pages::supplier.deliveries-index')->name('deliveries.index');

            Route::livewire('bills', 'pages::supplier.bills-index')->name('bills.index');
            Route::livewire('bills/{bill}', 'pages::supplier.bills-show')->name('bills.show');

            Route::livewire('payments', 'pages::supplier.payments-index')->name('payments.index');

            Route::livewire('contracts', 'pages::supplier.contracts-index')->name('contracts.index');
            Route::livewire('contracts/{contract}', 'pages::supplier.contracts-show')->name('contracts.show');

            Route::livewire('documents', 'pages::supplier.documents-index')->name('documents.index');

            Route::livewire('notifications', 'pages::supplier.notifications-index')->name('notifications.index');

            Route::livewire('profile', 'pages::supplier.profile')->name('profile.edit');

            Route::get('print/{type}/{id}', [SupplierDocumentController::class, 'print'])
                ->whereIn('type', ['purchase-order', 'bill', 'payment', 'contract'])
                ->middleware('throttle:prints')
                ->name('print');

            Route::get('pdf/{type}/{id}', [SupplierDocumentController::class, 'pdf'])
                ->whereIn('type', ['purchase-order', 'bill', 'payment', 'contract'])
                ->middleware('throttle:exports')
                ->name('pdf');
        });
    });
});
