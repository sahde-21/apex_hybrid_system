<?php

use App\Http\Controllers\CrmInteractionController;
use App\Http\Controllers\CustomerFeedbackController;
use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('crm')->group(function () {
    Route::middleware(['can:crm-interactions.read'])->name('crm-interactions.')->group(function () {
        Route::livewire('crm-interactions', 'pages::crm.crm-interactions-index')->name('index');
        Route::livewire('crm-interactions/create', 'pages::crm.crm-interactions-create')->middleware('can:crm-interactions.create')->name('create');
        Route::livewire('crm-interactions/{crmInteraction}/edit', 'pages::crm.crm-interactions-edit')->middleware('can:crm-interactions.update')->name('edit');

        Route::post('crm-interactions', [CrmInteractionController::class, 'store'])->middleware('can:crm-interactions.create')->name('store');
        Route::put('crm-interactions/{crmInteraction}', [CrmInteractionController::class, 'update'])->middleware('can:crm-interactions.update')->name('update');
        Route::delete('crm-interactions/{crmInteraction}', [CrmInteractionController::class, 'destroy'])->middleware('can:crm-interactions.delete')->name('destroy');
    });

    Route::middleware(['can:leads.read'])->name('leads.')->group(function () {
        Route::livewire('leads', 'pages::crm.leads-index')->name('index');
        Route::livewire('leads/create', 'pages::crm.leads-create')->middleware('can:leads.create')->name('create');
        Route::livewire('leads/{lead}/edit', 'pages::crm.leads-edit')->middleware('can:leads.update')->name('edit');

        Route::post('leads', [LeadController::class, 'store'])->middleware('can:leads.create')->name('store');
        Route::put('leads/{lead}', [LeadController::class, 'update'])->middleware('can:leads.update')->name('update');
        Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->middleware('can:leads.delete')->name('destroy');
    });

    Route::middleware(['can:customer-feedback.read'])->name('customer-feedback.')->group(function () {
        Route::livewire('customer-feedback', 'pages::crm.customer-feedback-index')->name('index');
        Route::livewire('customer-feedback/create', 'pages::crm.customer-feedback-create')->middleware('can:customer-feedback.create')->name('create');
        Route::livewire('customer-feedback/{customerFeedback}/edit', 'pages::crm.customer-feedback-edit')->middleware('can:customer-feedback.update')->name('edit');

        Route::post('customer-feedback', [CustomerFeedbackController::class, 'store'])->middleware('can:customer-feedback.create')->name('store');
        Route::put('customer-feedback/{customerFeedback}', [CustomerFeedbackController::class, 'update'])->middleware('can:customer-feedback.update')->name('update');
        Route::delete('customer-feedback/{customerFeedback}', [CustomerFeedbackController::class, 'destroy'])->middleware('can:customer-feedback.delete')->name('destroy');
    });
});
