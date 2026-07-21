<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Recommendations\RecommendationEngine;
use App\Support\Analytics\AnalyticsFilter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshRecommendationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(RecommendationEngine $engine): void
    {
        if (! config('intelligence.enabled', true)) {
            return;
        }

        $admin = User::role('super-admin')->first() ?? User::query()->first();
        if (! $admin) {
            return;
        }

        $engine->refresh($admin, AnalyticsFilter::default());
    }
}
