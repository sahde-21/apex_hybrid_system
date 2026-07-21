<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('administration')->group(function () {

    Route::middleware(['can:users.read'])->name('users.')->group(function () {
        Route::livewire('users', 'pages::administration.users-index')->name('index');
        Route::livewire('users/create', 'pages::administration.users-create')->middleware('can:users.create')->name('create');
        Route::livewire('users/{user}', 'pages::administration.users-show')->name('show');
        Route::livewire('users/{user}/edit', 'pages::administration.users-edit')->middleware('can:users.update')->name('edit');

        Route::post('users', [UserController::class, 'store'])
            ->middleware(['can:users.create', 'throttle:uploads'])
            ->name('store');
        Route::put('users/{user}', [UserController::class, 'update'])
            ->middleware(['can:users.update', 'throttle:uploads'])
            ->name('update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('can:users.delete')->name('destroy');
    });

    Route::middleware(['can:branches.read'])->name('branches.')->group(function () {
        Route::livewire('branches', 'pages::administration.branches-index')->name('index');
        Route::livewire('branches/create', 'pages::administration.branches-create')->middleware('can:branches.create')->name('create');
        Route::livewire('branches/{branch}/edit', 'pages::administration.branches-edit')->middleware('can:branches.update')->name('edit');

        Route::post('branches', [BranchController::class, 'store'])->middleware('can:branches.create')->name('store');
        Route::put('branches/{branch}', [BranchController::class, 'update'])->middleware('can:branches.update')->name('update');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->middleware('can:branches.delete')->name('destroy');
    });

    Route::middleware(['can:notification-templates.read'])->name('notification-templates.')->group(function () {
        Route::livewire('notification-templates', 'pages::administration.notification-templates-index')->name('index');
        Route::livewire('notification-templates/create', 'pages::administration.notification-templates-create')->middleware('can:notification-templates.create')->name('create');
        Route::livewire('notification-templates/{notificationTemplate}/edit', 'pages::administration.notification-templates-edit')->middleware('can:notification-templates.update')->name('edit');

        Route::post('notification-templates', [NotificationTemplateController::class, 'store'])->middleware('can:notification-templates.create')->name('store');
        Route::put('notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'update'])->middleware('can:notification-templates.update')->name('update');
        Route::delete('notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'destroy'])->middleware('can:notification-templates.delete')->name('destroy');
    });

    Route::middleware(['can:notifications.read'])->name('notifications.')->group(function () {
        Route::livewire('notifications', 'pages::administration.notifications-index')->name('index');
    });

    Route::middleware(['can:activities.read'])->name('activities.')->group(function () {
        Route::livewire('activities', 'pages::administration.activities-index')->name('index');
    });

    Route::middleware(['can:audit-logs.read'])->name('audit-logs.')->group(function () {
        Route::livewire('audit-logs', 'pages::administration.audit-logs-index')->name('index');
        Route::livewire('audit-logs/{auditLog}', 'pages::administration.audit-logs-show')->name('show');
    });

    Route::middleware(['can:settings.read'])->name('system-information.')->group(function () {
        Route::livewire('system-information', 'pages::administration.system-information')->name('index');
    });
});
