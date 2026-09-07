<?php

namespace App\Services\Deployment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QueueStatusService
{
    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $driver = (string) config('queue.default');
        $pending = null;
        $failed = null;
        $oldestMinutes = null;
        $warnings = [];

        if ($driver === 'database' && Schema::hasTable('jobs')) {
            $pending = (int) DB::table('jobs')->count();

            $oldest = DB::table('jobs')->orderBy('created_at')->value('created_at');
            if ($oldest) {
                $oldestMinutes = (int) now()->diffInMinutes($oldest);
            }
        }

        if (Schema::hasTable('failed_jobs')) {
            $failed = (int) DB::table('failed_jobs')->count();
        }

        if ($driver === 'sync') {
            $warnings[] = __('scf.release.queue_sync_warning');
        }

        $pendingWarn = (int) config('deployment.queue.pending_warning', 100);
        $pendingFail = (int) config('deployment.queue.pending_fail', 1000);
        $oldestWarn = (int) config('deployment.queue.oldest_job_warning_minutes', 60);

        if ($pending !== null && $pending >= $pendingFail) {
            $warnings[] = __('scf.release.queue_pending_critical', ['count' => $pending]);
        } elseif ($pending !== null && $pending >= $pendingWarn) {
            $warnings[] = __('scf.release.queue_pending_high', ['count' => $pending]);
        }

        if ($oldestMinutes !== null && $oldestMinutes >= $oldestWarn) {
            $warnings[] = __('scf.release.queue_oldest_warning', ['minutes' => $oldestMinutes]);
        }

        return [
            'driver' => $driver,
            'connection' => (string) config('queue.connections.'.$driver.'.connection', $driver),
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'oldest_pending_minutes' => $oldestMinutes,
            'retry_after' => config('queue.connections.'.$driver.'.retry_after'),
            'warnings' => $warnings,
        ];
    }

    public function isHealthy(): bool
    {
        try {
            $status = $this->status();

            foreach ($status['warnings'] as $warning) {
                if (str_contains((string) $warning, 'critical')) {
                    return false;
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
