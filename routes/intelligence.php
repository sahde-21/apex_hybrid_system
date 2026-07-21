<?php

use App\Http\Controllers\IntelligenceExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:intelligence.view'])->prefix('intelligence')->name('intelligence.')->group(function () {
    $permissions = [
        'executive' => 'intelligence.executive.view',
        'financial' => 'intelligence.financial.view',
        'sales' => 'intelligence.sales.view',
        'purchasing' => 'intelligence.purchasing.view',
        'inventory' => 'intelligence.inventory.view',
        'customers' => 'intelligence.customers.view',
        'suppliers' => 'intelligence.suppliers.view',
        'operations' => 'intelligence.operations.view',
        'forecasts' => 'intelligence.forecasts.view',
        'alerts' => 'intelligence.alerts.view',
        'recommendations' => 'intelligence.recommendations.view',
        'assistant' => 'intelligence.assistant.use',
    ];

    foreach ($permissions as $page => $permission) {
        Route::livewire($page, 'pages::intelligence.workspace')
            ->middleware("can:{$permission}")
            ->name($page);
    }

    Route::middleware(['can:intelligence.export', 'throttle:exports'])->group(function () {
        Route::get('export/{domain}/csv', [IntelligenceExportController::class, 'csv'])->name('export.csv');
        Route::get('export/{domain}/pdf', [IntelligenceExportController::class, 'pdf'])->name('export.pdf');
    });
});
