<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Recommendations\RecommendationEngine;
use App\Support\Analytics\AnalyticsFilter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RefreshRecommendationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(RecommendationEngine $engine): void
    {
        if (! config('intelligence.enabled', true)) {
            return;
        }

        $admin = User::role('super-admin')->first();
        if (! $admin) {
            Log::warning('intelligence.job.aborted_no_super_admin', [
                'job' => static::class,
            ]);

            return;
        }

        $engine->refresh($admin, AnalyticsFilter::default());
    }
}
