<?php

namespace App\Console\Commands;

use App\Support\Database\DatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup {--label=manual : Label appended to the backup filename} {--prune : Remove backups older than retention policy}';

    protected $description = 'Create a timestamped local database backup';

    public function handle(DatabaseBackupService $backups): int
    {
        try {
            $path = $backups->backup((string) $this->option('label'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(__('scf.performance.backup_created', ['path' => basename($path)]));

        if ($this->option('prune')) {
            $removed = $backups->pruneOldBackups();
            $this->info(__('scf.performance.backup_pruned', ['count' => $removed]));
        }

        return self::SUCCESS;
    }
}
