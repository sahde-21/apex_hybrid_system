<?php

namespace App\Console\Commands;

use App\Services\Deployment\QueueStatusService;
use Illuminate\Console\Command;

class QueueStatusCommand extends Command
{
    protected $signature = 'scf:queue-status {--json : Output machine-readable JSON}';

    protected $description = 'Report queue driver status and backlog warnings';

    public function handle(QueueStatusService $queues): int
    {
        $status = $queues->status();

        if ($this->option('json')) {
            $this->line((string) json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info(__('scf.release.queue_status_heading'));
        $this->table(
            [__('scf.release.info_key'), __('scf.release.info_value')],
            collect($status)->except('warnings')->map(fn ($value, $key) => [$key, is_array($value) ? json_encode($value) : (string) ($value ?? '—')])->values()->all(),
        );

        foreach ($status['warnings'] as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }
}
