<?php

use App\Http\Controllers\PwaController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/health/live', [\App\Http\Controllers\HealthController::class, 'live'])->name('health.live');
Route::get('/health/ready', [\App\Http\Controllers\HealthController::class, 'ready'])->name('health.ready');

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/offline', [PwaController::class, 'offline'])->name('pwa.offline');

Route::middleware(['auth', 'verified', 'can:dashboard.read'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/inventory.php';
require __DIR__.'/warehouses.php';
require __DIR__.'/contacts.php';
require __DIR__.'/purchase_orders.php';
require __DIR__.'/purchase_requests.php';
require __DIR__.'/rfqs.php';
require __DIR__.'/sale_orders.php';
require __DIR__.'/sales_documents.php';
require __DIR__.'/purchasing_documents.php';
require __DIR__.'/inventory_adjustments.php';
require __DIR__.'/hr.php';
require __DIR__.'/crm.php';
require __DIR__.'/accounting.php';
require __DIR__.'/administration.php';
require __DIR__.'/manufacturing.php';
require __DIR__.'/warehouse.php';
require __DIR__.'/marketing.php';
require __DIR__.'/projects.php';
require __DIR__.'/support.php';
require __DIR__.'/purchasing.php';
require __DIR__.'/print.php';
require __DIR__.'/export.php';
require __DIR__.'/pos.php';
require __DIR__.'/portal.php';
require __DIR__.'/supplier-portal.php';
require __DIR__.'/analytics.php';
require __DIR__.'/documents.php';
