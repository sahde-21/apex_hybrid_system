<?php

namespace App\Listeners;

use App\Support\Database\DatabaseBackupService;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Log;

class BackupBeforeDestructiveDatabaseCommand
{
    /** @var list<string> */
    private const DESTRUCTIVE_COMMANDS = [
        'migrate:fresh',
        'migrate:refresh',
        'migrate:reset',
        'db:wipe',
    ];

    public function __construct(private readonly DatabaseBackupService $backups) {}

    public function handle(CommandStarting $event): void
    {
        $command = (string) $event->command;

        if (! in_array($command, self::DESTRUCTIVE_COMMANDS, true)) {
            return;
        }

        if (app()->environment('testing')) {
            return;
        }

        if (! filter_var((string) env('ALLOW_DESTRUCTIVE_DB', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        try {
            $path = $this->backups->backupSqlite('pre-'.str_replace(':', '-', $command));
            Log::warning('database.backup.before_destructive', [
                'command' => $command,
                'backup' => $path,
            ]);
        } catch (\Throwable $e) {
            Log::error('database.backup.before_destructive_failed', [
                'command' => $command,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}