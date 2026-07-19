<?php

namespace App\Console\Commands;

use App\Support\Database\DatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup {--label=manual : Label appended to the backup filename}';

    protected $description = 'Create a timestamped backup of the local SQLite database';

    public function handle(DatabaseBackupService $backups): int
    {
        try {
            $path = $backups->backupSqlite((string) $this->option('label'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Database backup created: {$path}");

        return self::SUCCESS;
    }
}
