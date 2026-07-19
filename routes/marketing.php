<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\GiftCardController;
use App\Http\Controllers\LoyaltyProgramController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('marketing')->group(function () {

    Route::middleware(['can:campaigns.read'])->name('campaigns.')->group(function () {
        Route::livewire('campaigns', 'pages::marketing.campaigns-index')->name('index');
        Route::livewire('campaigns/create', 'pages::marketing.campaigns-create')->middleware('can:campaigns.create')->name('create');
        Route::livewire('campaigns/{campaign}/edit', 'pages::marketing.campaigns-edit')->middleware('can:campaigns.update')->name('edit');

        Route::post('campaigns', [CampaignController::class, 'store'])->middleware('can:campaigns.create')->name('store');
        Route::put('campaigns/{campaign}', [CampaignController::class, 'update'])->middleware('can:campaigns.update')->name('update');
        Route::delete('campaigns/{campaign}', [CampaignController::class, 'destroy'])->middleware('can:campaigns.delete')->name('destroy');
    });

    Route::middleware(['can:loyalty-programs.read'])->name('loyalty-programs.')->group(function () {
        Route::livewire('loyalty-programs', 'pages::marketing.loyalty-programs-index')->name('index');
        Route::livewire('loyalty-programs/create', 'pages::marketing.loyalty-programs-create')->middleware('can:loyalty-programs.create')->name('create');
        Route::livewire('loyalty-programs/{loyaltyProgram}/edit', 'pages::marketing.loyalty-programs-edit')->middleware('can:loyalty-programs.update')->name('edit');

        Route::post('loyalty-programs', [LoyaltyProgramController::class, 'store'])->middleware('can:loyalty-programs.create')->name('store');
        Route::put('loyalty-programs/{loyaltyProgram}', [LoyaltyProgramController::class, 'update'])->middleware('can:loyalty-programs.update')->name('update');
        Route::delete('loyalty-programs/{loyaltyProgram}', [LoyaltyProgramController::class, 'destroy'])->middleware('can:loyalty-programs.delete')->name('destroy');
    });

    Route::middleware(['can:coupons.read'])->name('coupons.')->group(function () {
        Route::livewire('coupons', 'pages::marketing.coupons-index')->name('index');
        Route::livewire('coupons/create', 'pages::marketing.coupons-create')->middleware('can:coupons.create')->name('create');
        Route::livewire('coupons/{coupon}/edit', 'pages::marketing.coupons-edit')->middleware('can:coupons.update')->name('edit');

        Route::post('coupons', [CouponController::class, 'store'])->middleware('can:coupons.create')->name('store');
        Route::put('coupons/{coupon}', [CouponController::class, 'update'])->middleware('can:coupons.update')->name('update');
        Route::delete('coupons/{coupon}', [CouponController::class, 'destroy'])->middleware('can:coupons.delete')->name('destroy');
    });

    Route::middleware(['can:gift-cards.read'])->name('gift-cards.')->group(function () {
        Route::livewire('gift-cards', 'pages::marketing.gift-cards-index')->name('index');
        Route::livewire('gift-cards/create', 'pages::marketing.gift-cards-create')->middleware('can:gift-cards.create')->name('create');
        Route::livewire('gift-cards/{giftCard}/edit', 'pages::marketing.gift-cards-edit')->middleware('can:gift-cards.update')->name('edit');

        Route::post('gift-cards', [GiftCardController::class, 'store'])->middleware('can:gift-cards.create')->name('store');
        Route::put('gift-cards/{giftCard}', [GiftCardController::class, 'update'])->middleware('can:gift-cards.update')->name('update');
        Route::delete('gift-cards/{giftCard}', [GiftCardController::class, 'destroy'])->middleware('can:gift-cards.delete')->name('destroy');
    });

    Route::middleware(['can:subscriptions.read'])->name('subscriptions.')->group(function () {
        Route::livewire('subscriptions', 'pages::marketing.subscriptions-index')->name('index');
        Route::livewire('subscriptions/create', 'pages::marketing.subscriptions-create')->middleware('can:subscriptions.create')->name('create');
        Route::livewire('subscriptions/{subscription}/edit', 'pages::marketing.subscriptions-edit')->middleware('can:subscriptions.update')->name('edit');

        Route::post('subscriptions', [SubscriptionController::class, 'store'])->middleware('can:subscriptions.create')->name('store');
        Route::put('subscriptions/{subscription}', [SubscriptionController::class, 'update'])->middleware('can:subscriptions.update')->name('update');
        Route::delete('subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])->middleware('can:subscriptions.delete')->name('destroy');
    });
});
