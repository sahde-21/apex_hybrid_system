<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class DatabaseBackupService
{
    public function backupDirectory(): string
    {
        $configured = config('performance.backup.directory');

        $directory = is_string($configured) && $configured !== ''
            ? $configured
            : database_path('backups');

        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /**
     * Create a timestamped backup for the active database connection.
     *
     * @return string Absolute path to the backup file
     */
    public function backup(?string $label = null): string
    {
        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");

        return match ($driver) {
            'sqlite' => $this->backupSqlite($label),
            'pgsql' => $this->backupPostgres($label),
            default => throw new RuntimeException("Unsupported backup driver [{$driver}]. Supported: sqlite, pgsql."),
        };
    }

    /**
     * @return string Absolute path to the backup file
     */
    public function backupSqlite(?string $label = null): string
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($database === '' || $database === ':memory:') {
            throw new RuntimeException('Cannot backup an in-memory SQLite database.');
        }

        if (! is_file($database)) {
            throw new RuntimeException("SQLite database file not found: {$database}");
        }

        $destination = $this->backupPath('sqlite', $label);

        if (! File::copy($database, $destination)) {
            throw new RuntimeException("Failed to write database backup to {$destination}");
        }

        return $destination;
    }

    /**
     * @return string Absolute path to the backup file
     */
    public function backupPostgres(?string $label = null): string
    {
        $connection = config("database.connections.{$this->connectionName()}");
        $destination = $this->backupPath('sql', $label);

        $host = $connection['host'] ?? '127.0.0.1';
        $port = (string) ($connection['port'] ?? 5432);
        $database = (string) ($connection['database'] ?? '');
        $username = (string) ($connection['username'] ?? '');

        if ($database === '') {
            throw new RuntimeException('PostgreSQL database name is not configured.');
        }

        $command = [
            'pg_dump',
            '--format=plain',
            '--no-owner',
            '--no-privileges',
            '--host='.$host,
            '--port='.$port,
            '--username='.$username,
            '--file='.$destination,
            $database,
        ];

        $env = [];
        if (! empty($connection['password'])) {
            $env['PGPASSWORD'] = (string) $connection['password'];
        }

        $result = Process::env($env)->run($command);

        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput()) ?: 'pg_dump failed.');
        }

        if (! is_file($destination) || filesize($destination) === 0) {
            throw new RuntimeException('PostgreSQL backup file was not created.');
        }

        return $destination;
    }

    public function pruneOldBackups(): int
    {
        $retentionDays = (int) config('performance.backup.retention_days', 14);
        $cutoff = now()->subDays($retentionDays)->getTimestamp();
        $removed = 0;

        foreach (File::files($this->backupDirectory()) as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $removed++;
            }
        }

        return $removed;
    }

    protected function backupPath(string $extension, ?string $label): string
    {
        $stamp = now()->format('Ymd_His');
        $suffix = $label ? '_'.preg_replace('/[^A-Za-z0-9_-]+/', '_', $label) : '';

        return $this->backupDirectory().DIRECTORY_SEPARATOR."database_{$stamp}{$suffix}.{$extension}";
    }

    protected function connectionName(): string
    {
        return (string) config('database.default');
    }
}
