<?php

use App\Jobs\EvaluateSmartAlertsJob;
use App\Jobs\GenerateDailyExecutiveSnapshotJob;
use App\Jobs\RefreshRecommendationsJob;
use App\Models\User;
use App\Services\Alerts\SmartAlertService;
use App\Services\Intelligence\IntelligenceSnapshotService;
use App\Services\Recommendations\RecommendationEngine;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Log;

test('intelligence jobs abort without selecting an arbitrary user when no super-admin exists', function () {
    $this->seed(RolePermissionSeeder::class);
    Log::spy();

    // Ensure a non-privileged user exists that must not be selected.
    User::factory()->create([
        'email' => 'regular-user@example.com',
    ]);

    expect(User::role('super-admin')->exists())->toBeFalse();

    app(RefreshRecommendationsJob::class)->handle(app(RecommendationEngine::class));
    app(EvaluateSmartAlertsJob::class)->handle(app(SmartAlertService::class));
    app(GenerateDailyExecutiveSnapshotJob::class)->handle(app(IntelligenceSnapshotService::class));

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context = []) => $message === 'intelligence.job.aborted_no_super_admin')
        ->atLeast()
        ->times(3);
});
