<?php

use App\Models\Product;
use App\Services\Performance\GlobalSearchService;
use App\Services\Performance\HealthCheckService;
use App\Support\Performance\PerformanceCache;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('health readiness service reports core checks', function () {
    $result = app(HealthCheckService::class)->readiness();

    expect($result['checks']['database'])->toBeTrue()
        ->and($result['checks']['cache'])->toBeTrue()
        ->and($result['checks']['storage'])->toBeTrue();
});

test('web health live endpoint returns ok', function () {
    $this->get('/health/live')
        ->assertOk()
        ->assertJsonPath('status', 'ok');
});

test('web health ready endpoint returns checks', function () {
    $this->get('/health/ready')
        ->assertOk()
        ->assertJsonStructure(['status', 'checks' => ['database', 'cache', 'queue', 'storage']]);
});

test('global search respects permissions', function () {
    $user = actingAsUserWithPermissions(['products.read']);

    $results = app(GlobalSearchService::class)->search($user, 'test');

    expect($results)->toBeArray();
});

test('global search hides products without permission', function () {
    $user = actingAsUserWithPermissions(['contacts.read']);

    Product::factory()->create(['name' => 'UniquePerfWidget', 'sku' => 'UPW-001']);

    $results = app(GlobalSearchService::class)->search($user, 'UniquePerfWidget');

    expect(collect($results)->pluck('module'))->not->toContain('products');
});

test('performance cache stores reference data', function () {
    PerformanceCache::forgetReferenceData();

    $first = PerformanceCache::currencies();
    $second = PerformanceCache::currencies();

    expect($first)->toBe($second);
});

test('environment validation command runs', function () {
    Artisan::call('scf:validate-env');

    expect(Artisan::output())->toContain('Environment validation');
});

test('security headers include baseline protections', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});

test('dashboard metrics are cached per user', function () {
    $user = actingAsUserWithPermissions(['products.read', 'dashboard.read']);

    $service = app(\App\Services\Dashboard\DashboardMetricsService::class);

    $first = $service->metrics($user);
    $second = $service->metrics($user);

    expect($first)->toBe($second);
});
