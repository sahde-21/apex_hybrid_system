<?php

namespace App\Services\Intelligence;

use App\Models\IntelligenceRun;
use App\Models\IntelligenceSnapshot;
use App\Models\User;
use App\Support\Analytics\AnalyticsFilter;

class IntelligenceSnapshotService
{
    public function __construct(
        protected ExecutiveAnalyticsService $executive,
    ) {}

    public function storeExecutive(User $user, AnalyticsFilter $filter, string $type = 'executive_daily'): IntelligenceSnapshot
    {
        $run = IntelligenceRun::query()->create([
            'type' => $type,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $payload = $this->executive->dashboard($user, $filter);
        $snapshot = IntelligenceSnapshot::query()->create([
            'type' => $type,
            'category' => 'executive',
            'branch_id' => $filter->branchId(),
            'payload' => $payload,
            'meta' => ['user_id' => $user->id],
            'generated_at' => now(),
            'expires_at' => now()->addDays((int) config('intelligence.snapshot_retention_days', 30)),
        ]);

        $run->update([
            'status' => 'completed',
            'records_generated' => 1,
            'finished_at' => now(),
            'duration_ms' => (int) $run->started_at->diffInMilliseconds(now()),
        ]);

        return $snapshot;
    }

    public function pruneExpired(): int
    {
        return IntelligenceSnapshot::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }
}
