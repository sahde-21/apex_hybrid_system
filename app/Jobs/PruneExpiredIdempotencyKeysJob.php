<?php

namespace App\Jobs;

use App\Models\ApiIdempotencyKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PruneExpiredIdempotencyKeysJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $hours = (int) config('performance.idempotency.prune_after_hours', 48);

        ApiIdempotencyKey::query()
            ->where('expires_at', '<=', now()->subHours($hours))
            ->delete();
    }
}
