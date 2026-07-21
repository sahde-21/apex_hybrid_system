<?php

namespace App\Console\Commands;

use App\Support\Performance\PerformanceCache;
use Illuminate\Console\Command;

class WarmPerformanceCacheCommand extends Command
{
    protected $signature = 'scf:warm-cache';

    protected $description = 'Warm safe reference-data caches';

    public function handle(): int
    {
        PerformanceCache::currencies();
        PerformanceCache::taxRates();

        $this->info(__('scf.performance.cache_warmed'));

        return self::SUCCESS;
    }
}
