<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Alerts\SmartAlertService;
use App\Support\Analytics\AnalyticsFilter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EvaluateSmartAlertsJob implements ShouldQueue
{
    use Queueable;

    public function handle(SmartAlertService $alerts): void
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

        $alerts->evaluate($admin, AnalyticsFilter::default());
    }
}
