<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\File;
use RuntimeException;

class DatabaseBackupService
{
    public function backupDirectory(): string
    {
        $directory = database_path('backups');

        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /**
     * Create a timestamped backup of the configured SQLite database file.
     *
     * @return string Absolute path to the backup file
     */
    public function backupSqlite(?string $label = null): string
    {
        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");

        if ($driver !== 'sqlite') {
            throw new RuntimeException("Automatic file backups are only supported for sqlite (got [{$driver}]).");
        }

        $database = (string) config("database.connections.{$connection}.database");

        if ($database === '' || $database === ':memory:') {
            throw new RuntimeException('Cannot backup an in-memory SQLite database.');
        }

        if (! is_file($database)) {
            throw new RuntimeException("SQLite database file not found: {$database}");
        }

        $stamp = now()->format('Ymd_His');
        $suffix = $label ? '_'.preg_replace('/[^A-Za-z0-9_-]+/', '_', $label) : '';
        $destination = $this->backupDirectory().DIRECTORY_SEPARATOR."database_{$stamp}{$suffix}.sqlite";

        if (! File::copy($database, $destination)) {
            throw new RuntimeException("Failed to write database backup to {$destination}");
        }

        return $destination;
    }
}
