<?php

use App\Http\Controllers\KnowledgeBaseArticleController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('support')->group(function () {

    Route::middleware(['can:tickets.read'])->name('tickets.')->group(function () {
        Route::livewire('tickets', 'pages::support.tickets-index')->name('index');
        Route::livewire('tickets/create', 'pages::support.tickets-create')->middleware('can:tickets.create')->name('create');
        Route::livewire('tickets/{ticket}/edit', 'pages::support.tickets-edit')->middleware('can:tickets.update')->name('edit');

        Route::post('tickets', [TicketController::class, 'store'])->middleware('can:tickets.create')->name('store');
        Route::put('tickets/{ticket}', [TicketController::class, 'update'])->middleware('can:tickets.update')->name('update');
        Route::delete('tickets/{ticket}', [TicketController::class, 'destroy'])->middleware('can:tickets.delete')->name('destroy');
    });

    Route::middleware(['can:knowledge-base.read'])->name('knowledge-base.')->group(function () {
        Route::livewire('knowledge-base', 'pages::support.knowledge-base-index')->name('index');
        Route::livewire('knowledge-base/create', 'pages::support.knowledge-base-create')->middleware('can:knowledge-base.create')->name('create');
        Route::livewire('knowledge-base/{knowledgeBaseArticle}/edit', 'pages::support.knowledge-base-edit')->middleware('can:knowledge-base.update')->name('edit');

        Route::post('knowledge-base', [KnowledgeBaseArticleController::class, 'store'])->middleware('can:knowledge-base.create')->name('store');
        Route::put('knowledge-base/{knowledgeBaseArticle}', [KnowledgeBaseArticleController::class, 'update'])->middleware('can:knowledge-base.update')->name('update');
        Route::delete('knowledge-base/{knowledgeBaseArticle}', [KnowledgeBaseArticleController::class, 'destroy'])->middleware('can:knowledge-base.delete')->name('destroy');
    });
});
