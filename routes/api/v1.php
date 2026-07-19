<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\DocumentationController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)
    ->middleware('throttle:60,1')
    ->name('health');

Route::get('/docs', DocumentationController::class)
    ->middleware(array_filter([
        'throttle:30,1',
        app()->isProduction() ? 'auth:sanctum' : null,
    ]))
    ->name('docs');

Route::middleware('throttle:api-auth')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
});

Route::middleware(['auth:sanctum', 'api.active', 'throttle:api'])->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');

    Route::get('/tokens', [TokenController::class, 'index'])->name('tokens.index');
    Route::post('/tokens', [TokenController::class, 'store'])->name('tokens.store');
    Route::delete('/tokens/others', [TokenController::class, 'destroyOthers'])->name('tokens.destroy-others');
    Route::get('/tokens/{token}', [TokenController::class, 'show'])->whereNumber('token')->name('tokens.show');
    Route::delete('/tokens/{token}', [TokenController::class, 'destroy'])->whereNumber('token')->name('tokens.destroy');
});
