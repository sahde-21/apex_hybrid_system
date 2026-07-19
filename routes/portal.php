<?php

use App\Http\Controllers\Portal\Auth\PortalAuthController;
use App\Http\Controllers\Portal\PortalDocumentController;
use App\Http\Middleware\EnsurePortalEmailIsVerified;
use App\Http\Middleware\UsePortalGuard;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->name('portal.')->middleware(UsePortalGuard::class)->group(function () {
    Route::middleware('guest:portal')->group(function () {
        Route::get('login', [PortalAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [PortalAuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login.store');

        Route::get('forgot-password', [PortalAuthController::class, 'showForgotPassword'])->name('password.request');
        Route::post('forgot-password', [PortalAuthController::class, 'sendResetLink'])
            ->middleware('throttle:login')
            ->name('password.email');

        Route::get('reset-password/{token}', [PortalAuthController::class, 'showResetPassword'])->name('password.reset');
        Route::post('reset-password', [PortalAuthController::class, 'resetPassword'])->name('password.update');

        Route::get('two-factor-challenge', [PortalAuthController::class, 'showTwoFactorChallenge'])->name('two-factor.login');
        Route::post('two-factor-challenge', [PortalAuthController::class, 'verifyTwoFactor'])
            ->middleware('throttle:login')
            ->name('two-factor.login.store');
    });

    Route::get('verify-email/{id}/{hash}', [PortalAuthController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::middleware('auth:portal')->group(function () {
        Route::post('logout', [PortalAuthController::class, 'logout'])->name('logout');

        Route::get('verify-email', [PortalAuthController::class, 'showVerifyNotice'])->name('verification.notice');
        Route::post('verify-email', [PortalAuthController::class, 'sendVerification'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::middleware(EnsurePortalEmailIsVerified::class)->group(function () {
            Route::livewire('/', 'pages::portal.dashboard')->name('dashboard');

            Route::livewire('orders', 'pages::portal.orders-index')->name('orders.index');
            Route::livewire('orders/{saleOrder}', 'pages::portal.orders-show')->name('orders.show');

            Route::livewire('quotations', 'pages::portal.quotations-index')->name('quotations.index');
            Route::livewire('quotations/{quotation}', 'pages::portal.quotations-show')->name('quotations.show');

            Route::livewire('invoices', 'pages::portal.invoices-index')->name('invoices.index');
            Route::livewire('invoices/{invoice}', 'pages::portal.invoices-show')->name('invoices.show');

            Route::livewire('payments', 'pages::portal.payments-index')->name('payments.index');

            Route::livewire('loyalty', 'pages::portal.loyalty-index')->name('loyalty.index');

            Route::livewire('gift-cards', 'pages::portal.gift-cards-index')->name('gift-cards.index');

            Route::livewire('tickets', 'pages::portal.tickets-index')->name('tickets.index');
            Route::livewire('tickets/create', 'pages::portal.tickets-create')->name('tickets.create');
            Route::livewire('tickets/{ticket}', 'pages::portal.tickets-show')->name('tickets.show');

            Route::livewire('documents', 'pages::portal.documents-index')->name('documents.index');

            Route::livewire('notifications', 'pages::portal.notifications-index')->name('notifications.index');

            Route::livewire('profile', 'pages::portal.profile')->name('profile.edit');

            Route::get('print/{type}/{id}', [PortalDocumentController::class, 'print'])
                ->whereIn('type', ['invoice', 'quotation', 'sale-order', 'payment'])
                ->middleware('throttle:prints')
                ->name('print');

            Route::get('pdf/{type}/{id}', [PortalDocumentController::class, 'pdf'])
                ->whereIn('type', ['invoice'])
                ->middleware('throttle:exports')
                ->name('pdf');
        });
    });
});
