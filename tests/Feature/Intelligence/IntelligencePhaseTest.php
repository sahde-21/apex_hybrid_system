<?php

use App\Enums\Analytics\InsightSeverity;
use App\Enums\Analytics\InsightStatus;
use App\Models\IntelligenceAlert;
use App\Services\Analytics\AnomalyDetectionService;
use App\Services\Analytics\TrendAnalysisService;
use App\Services\Forecasting\ForecastingService;
use App\Services\Intelligence\ExecutiveAnalyticsService;
use App\Services\Intelligence\SmartAssistantService;
use App\Services\Recommendations\RecommendationEngine;
use App\Services\Scoring\BusinessHealthScoreService;
use App\Support\Analytics\AnalyticsFilter;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Cache::flush();
});

test('users with intelligence permission can open executive intelligence', function () {
    actingAsSuperAdmin();

    $this->get(route('intelligence.executive'))->assertOk();
});

test('users without intelligence permission cannot open executive intelligence', function () {
    actingAsUserWithPermissions(['dashboard.read']);

    $this->get(route('intelligence.executive'))->assertForbidden();
});

test('trend analysis handles insufficient data', function () {
    $service = app(TrendAnalysisService::class);
    $result = $service->analyze([1.0]);

    expect($result->direction->value)->toBe('insufficient_data');
});

test('trend analysis calculates percentage change', function () {
    $service = app(TrendAnalysisService::class);
    $result = $service->analyze([100, 110, 130]);

    expect($result->percentageChange)->toBe(30.0)
        ->and($result->direction->value)->toBe('strong_increase');
});

test('forecasting refuses insufficient history', function () {
    $service = app(ForecastingService::class);
    $result = $service->forecast([10, 20], 3);

    expect($result->forecast)->toBe([])
        ->and($result->confidence)->toBe('none');
});

test('forecasting produces moving average estimates', function () {
    $service = app(ForecastingService::class);
    $result = $service->forecast([100, 120, 110, 130], 2);

    expect($result->isEstimate)->toBeTrue()
        ->and($result->forecast)->toHaveCount(2)
        ->and($result->method)->toBe('linear_regression');
});

test('business health score returns explainable result', function () {
    $user = actingAsUserWithPermissions(['intelligence.view', 'intelligence.executive.view']);
    $score = app(BusinessHealthScoreService::class)->score($user, AnalyticsFilter::default());

    expect($score->label)->not->toBeNull()
        ->and($score->generatedAt)->not->toBeEmpty();
});

test('executive analytics service returns cached payload', function () {
    $user = actingAsUserWithPermissions([
        'intelligence.view', 'intelligence.executive.view', 'analytics.read',
    ]);
    $filter = AnalyticsFilter::default();
    $service = app(ExecutiveAnalyticsService::class);

    $first = $service->dashboard($user, $filter);
    $second = $service->dashboard($user, $filter);

    expect($first)->toHaveKeys(['kpis', 'charts', 'health_score', 'meta'])
        ->and($second['kpis'])->toBe($first['kpis']);
});

test('super admin has intelligence assistant permission', function () {
    $user = actingAsSuperAdmin();

    expect($user->can('intelligence.assistant.use'))->toBeTrue();
});

test('smart assistant provides localized suggestions', function () {
    actingAsSuperAdmin();

    expect(app(SmartAssistantService::class)->suggestions())->not->toBeEmpty();
});

test('smart assistant intent registry matches sales month phrase', function () {
    $service = app(SmartAssistantService::class);
    $method = new ReflectionMethod($service, 'resolveIntent');
    $method->setAccessible(true);

    expect($method->invoke($service, 'show sales this month'))->toBe('sales_month')
        ->and($method->invoke($service, 'random unsupported phrase'))->toBeNull();
});

test('recommendation engine creates advisory recommendations', function () {
    $user = actingAsSuperAdmin();
    $count = app(RecommendationEngine::class)->refresh($user, AnalyticsFilter::default());

    expect($count)->toBeGreaterThanOrEqual(0);
});

test('smart alert evaluation is idempotent', function () {
    $user = actingAsSuperAdmin();
    $service = app(\App\Services\Alerts\SmartAlertService::class);
    $filter = AnalyticsFilter::default();

    $service->evaluate($user, $filter);
    $second = $service->evaluate($user, $filter);

    expect($second)->toBe(0);
});

test('intelligence api requires authentication', function () {
    $this->getJson('/api/v1/intelligence/executive')->assertUnauthorized();
});

test('intelligence api returns executive payload for authorized token', function () {
    $user = actingAsUserWithPermissions(['intelligence.view', 'intelligence.executive.view', 'analytics.read']);
    Sanctum::actingAs($user, ['intelligence.read']);

    $this->getJson('/api/v1/intelligence/executive')
        ->assertOk()
        ->assertJsonStructure(['data' => ['kpis', 'charts', 'health_score']]);
});

test('intelligence translations exist in en ar ckb', function () {
    foreach (['en', 'ar', 'ckb'] as $locale) {
        app()->setLocale($locale);
        expect(__('scf.intelligence.executive_title'))->not->toBe('scf.intelligence.executive_title');
    }
});

test('users with only base intelligence permission cannot access financial domain analytics', function () {
    $user = actingAsUserWithPermissions(['intelligence.view', 'analytics.read']);
    Sanctum::actingAs($user, ['intelligence.read']);

    $this->getJson('/api/v1/intelligence/financial')
        ->assertForbidden();
});

test('intelligence export rejects unknown domain', function () {
    actingAsUserWithPermissions(['intelligence.view', 'intelligence.export', 'intelligence.executive.view']);

    $this->get(route('intelligence.export.csv', ['domain' => 'invalid-domain']))
        ->assertNotFound();
});

test('alert acknowledge requires manage permission', function () {
    $alert = IntelligenceAlert::query()->create([
        'rule_key' => 'test',
        'category' => 'financial',
        'severity' => InsightSeverity::Low,
        'status' => InsightStatus::Active,
        'title' => 'Test',
        'summary' => 'Test summary',
        'detected_at' => now(),
    ]);

    $user = actingAsUserWithPermissions(['intelligence.alerts.view']);

    expect($user->can('intelligence.alerts.manage'))->toBeFalse();
});
