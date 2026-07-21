<?php

namespace App\Jobs;

use App\Services\Intelligence\IntelligenceSnapshotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PruneExpiredIntelligenceSnapshotsJob implements ShouldQueue
{
    use Queueable;

    public function handle(IntelligenceSnapshotService $snapshots): void
    {
        $snapshots->pruneExpired();
    }
}
