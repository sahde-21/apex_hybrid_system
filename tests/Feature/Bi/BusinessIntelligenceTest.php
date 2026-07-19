<?php

use App\Services\Bi\BiAnalyticsService;
use App\Services\Bi\BiKpiService;
use App\Services\Bi\BiReportService;
use App\Support\Bi\BiFilter;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    config(['bi.cache_ttl' => 60]);
    Cache::flush();
});

test('users with analytics permission can open executive hub', function () {
    actingAsSuperAdmin();

    $this->get(route('analytics.hub'))->assertOk();
});

test('users without analytics permission cannot open executive hub', function () {
    actingAsUserWithPermissions(['dashboard.read']);

    $this->get(route('analytics.hub'))->assertForbidden();
});

test('users with analytics permission can open reports hub', function () {
    actingAsSuperAdmin();

    $this->get(route('analytics.reports'))->assertOk();
});

test('executive hub livewire renders kpis for dashboard packs', function () {
    $user = actingAsSuperAdmin();

    Livewire::actingAs($user)
        ->test('pages::analytics.executive-hub')
        ->assertSee(__('scf.bi.hub_title'))
        ->set('dashboard', 'finance')
        ->assertSee(__('scf.bi.dashboard_finance'))
        ->assertHasNoErrors();
});

test('bi analytics service returns cached dashboard payload', function () {
    $user = actingAsSuperAdmin();
    $filter = BiFilter::fromArray([
        'from' => now()->startOfMonth()->toDateString(),
        'to' => now()->toDateString(),
        'dashboard' => 'ceo',
    ]);

    $service = app(BiAnalyticsService::class);
    $first = $service->dashboard($user, $filter);
    $second = $service->dashboard($user, $filter);

    expect($first)->toHaveKeys(['filter', 'dashboard', 'kpis', 'charts', 'rankings'])
        ->and($first['dashboard'])->toBe('ceo')
        ->and($first['kpis'])->toHaveKey('revenue')
        ->and($second['kpis']['revenue'])->toBe($first['kpis']['revenue']);
});

test('bi kpi service respects permission gates', function () {
    $user = actingAsUserWithPermissions(['analytics.read']);
    $filter = BiFilter::fromArray([
        'from' => now()->startOfMonth()->toDateString(),
        'to' => now()->toDateString(),
    ]);

    $kpis = app(BiKpiService::class)->kpis($user, $filter);

    expect($kpis['revenue'])->toBe(0.0)
        ->and($kpis['inventory_value'])->toBe(0.0)
        ->and($kpis['payroll_cost'])->toBe(0.0);
});

test('executive report export requires analytics permission', function () {
    actingAsUserWithPermissions(['dashboard.read']);

    $this->get(route('analytics.export.csv', ['type' => 'executive']))->assertForbidden();
});

test('authorized users can export executive csv', function () {
    actingAsSuperAdmin();

    $this->get(route('analytics.export.csv', [
        'type' => 'executive',
        'from' => now()->startOfMonth()->toDateString(),
        'to' => now()->toDateString(),
    ]))->assertOk();
});

test('financial report export requires financial reports permission', function () {
    actingAsUserWithPermissions(['analytics.read']);

    $this->get(route('analytics.export.csv', ['type' => 'financial']))->assertForbidden();
});

test('bi report service returns structured executive report', function () {
    $user = actingAsSuperAdmin();
    $filter = BiFilter::fromArray([
        'from' => now()->startOfMonth()->toDateString(),
        'to' => now()->toDateString(),
    ]);

    $report = app(BiReportService::class)->report($user, 'executive', $filter);

    expect($report)->toHaveKeys(['title', 'headers', 'rows'])
        ->and($report['headers'])->not->toBeEmpty()
        ->and($report['rows'])->not->toBeEmpty();
});

test('bi translations exist for english arabic and kurdish', function () {
    foreach (['en', 'ar', 'ckb'] as $locale) {
        app()->setLocale($locale);
        expect(__('scf.bi.brand'))->not->toBe('scf.bi.brand')
            ->and(__('scf.bi.hub_title'))->not->toBe('scf.bi.hub_title')
            ->and(__('scf.bi.report_executive'))->not->toBe('scf.bi.report_executive');
    }
});
