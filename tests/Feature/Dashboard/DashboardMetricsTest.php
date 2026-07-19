<?php

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('dashboard page renders for users with dashboard.read', function () {
    actingAsRole('cashier');

    $this->get(route('dashboard'))->assertOk();

    Livewire::test('pages::dashboard')
        ->assertOk()
        ->assertSee(__('scf.dashboard_page.welcome'));
});

test('dashboard metrics include product counts', function () {
    actingAsSuperAdmin();
    Product::factory()->count(3)->create();

    $metrics = app(DashboardMetricsService::class)->metrics();

    expect($metrics['products'])->toBe(3)
        ->and($metrics)->toHaveKeys([
            'contacts',
            'sale_orders',
            'invoices',
            'expenses_total',
            'employees',
            'open_tickets',
            'low_stock_products',
        ]);
});

test('recent activity is hidden without audit-logs.read', function () {
    $user = User::factory()->create();
    $user->assignRole('cashier');
    $this->actingAs($user);

    AuditLog::query()->create([
        'user_id' => $user->id,
        'auditable_type' => User::class,
        'auditable_id' => $user->id,
        'action' => 'updated',
        'old_values' => null,
        'new_values' => ['name' => 'x'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
    ]);

    expect(app(DashboardMetricsService::class)->recentActivity())->toHaveCount(0);
});

test('recent activity is visible with audit-logs.read', function () {
    actingAsSuperAdmin();

    AuditLog::query()->create([
        'user_id' => auth()->id(),
        'auditable_type' => User::class,
        'auditable_id' => auth()->id(),
        'action' => 'updated',
        'old_values' => null,
        'new_values' => ['name' => 'y'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
    ]);

    expect(app(DashboardMetricsService::class)->recentActivity()->count())->toBeGreaterThan(0);
});
