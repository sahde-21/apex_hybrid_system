<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\TimeLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('projects')->group(function () {

    Route::middleware(['can:contracts.read'])->name('contracts.')->group(function () {
        Route::livewire('contracts', 'pages::projects.contracts-index')->name('index');
        Route::livewire('contracts/create', 'pages::projects.contracts-create')->middleware('can:contracts.create')->name('create');
        Route::livewire('contracts/{contract}/edit', 'pages::projects.contracts-edit')->middleware('can:contracts.update')->name('edit');

        Route::post('contracts', [ContractController::class, 'store'])->middleware('can:contracts.create')->name('store');
        Route::put('contracts/{contract}', [ContractController::class, 'update'])->middleware('can:contracts.update')->name('update');
        Route::delete('contracts/{contract}', [ContractController::class, 'destroy'])->middleware('can:contracts.delete')->name('destroy');
    });

    Route::middleware(['can:project-tasks.read'])->name('project-tasks.')->group(function () {
        Route::livewire('project-tasks', 'pages::projects.project-tasks-index')->name('index');
        Route::livewire('project-tasks/create', 'pages::projects.project-tasks-create')->middleware('can:project-tasks.create')->name('create');
        Route::livewire('project-tasks/{projectTask}/edit', 'pages::projects.project-tasks-edit')->middleware('can:project-tasks.update')->name('edit');

        Route::post('project-tasks', [ProjectTaskController::class, 'store'])->middleware('can:project-tasks.create')->name('store');
        Route::put('project-tasks/{projectTask}', [ProjectTaskController::class, 'update'])->middleware('can:project-tasks.update')->name('update');
        Route::delete('project-tasks/{projectTask}', [ProjectTaskController::class, 'destroy'])->middleware('can:project-tasks.delete')->name('destroy');
    });

    Route::middleware(['can:time-logs.read'])->name('time-logs.')->group(function () {
        Route::livewire('time-logs', 'pages::projects.time-logs-index')->name('index');
        Route::livewire('time-logs/create', 'pages::projects.time-logs-create')->middleware('can:time-logs.create')->name('create');
        Route::livewire('time-logs/{timeLog}/edit', 'pages::projects.time-logs-edit')->middleware('can:time-logs.update')->name('edit');

        Route::post('time-logs', [TimeLogController::class, 'store'])->middleware('can:time-logs.create')->name('store');
        Route::put('time-logs/{timeLog}', [TimeLogController::class, 'update'])->middleware('can:time-logs.update')->name('update');
        Route::delete('time-logs/{timeLog}', [TimeLogController::class, 'destroy'])->middleware('can:time-logs.delete')->name('destroy');
    });
});
