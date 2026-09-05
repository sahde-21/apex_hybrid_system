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

    /**
     * @return list<array{filename: string, path: string, size: int, modified_at: string}>
     */
    public function listBackups(): array
    {
        $backups = [];

        foreach (File::files($this->backupDirectory()) as $file) {
            $backups[] = [
                'filename' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'modified_at' => date('c', $file->getMTime()),
            ];
        }

        usort($backups, fn (array $a, array $b) => strcmp($b['modified_at'], $a['modified_at']));

        return $backups;
    }

    /**
     * @return array{valid: bool, driver: string|null, size: int, message: string}
     */
    public function verifyBackup(string $backupPath): array
    {
        $resolved = $this->resolveBackupPath($backupPath);

        if (! is_file($resolved)) {
            return [
                'valid' => false,
                'driver' => null,
                'size' => 0,
                'message' => __('scf.release.backup_not_found'),
            ];
        }

        $size = (int) filesize($resolved);

        if ($size === 0) {
            return [
                'valid' => false,
                'driver' => null,
                'size' => 0,
                'message' => __('scf.release.backup_empty'),
            ];
        }

        $extension = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
        $driver = match ($extension) {
            'sqlite' => 'sqlite',
            'sql' => 'pgsql',
            default => null,
        };

        if ($driver === null) {
            return [
                'valid' => false,
                'driver' => null,
                'size' => $size,
                'message' => __('scf.release.backup_unsupported_extension', ['extension' => $extension]),
            ];
        }

        if ($driver === 'sqlite') {
            $valid = $this->verifySqliteBackup($resolved);
        } else {
            $valid = $this->verifySqlBackup($resolved);
        }

        return [
            'valid' => $valid,
            'driver' => $driver,
            'size' => $size,
            'message' => $valid ? __('scf.release.backup_verify_ok') : __('scf.release.backup_verify_fail'),
        ];
    }

    /**
     * @return array{dry_run: bool, message: string, safety_backup?: string}
     */
    public function restore(string $backupPath, bool $execute = false): array
    {
        $verification = $this->verifyBackup($backupPath);

        if (! $verification['valid'] || $verification['driver'] === null) {
            return [
                'dry_run' => ! $execute,
                'message' => $verification['message'],
            ];
        }

        if (! $execute) {
            return [
                'dry_run' => true,
                'message' => __('scf.release.restore_dry_run', [
                    'driver' => $verification['driver'],
                    'file' => basename($this->resolveBackupPath($backupPath)),
                ]),
            ];
        }

        if (! app()->isDownForMaintenance()) {
            return [
                'dry_run' => false,
                'message' => __('scf.release.restore_requires_maintenance'),
            ];
        }

        $safetyBackup = $this->backup(config('deployment.backup.pre_restore_label'));

        $resolved = $this->resolveBackupPath($backupPath);

        if ($verification['driver'] === 'sqlite') {
            $this->restoreSqlite($resolved);

            return [
                'dry_run' => false,
                'message' => __('scf.release.restore_sqlite_ok'),
                'safety_backup' => $safetyBackup,
            ];
        }

        $this->restorePostgres($resolved);

        return [
            'dry_run' => false,
            'message' => __('scf.release.restore_postgres_ok'),
            'safety_backup' => $safetyBackup,
        ];
    }

    public function resolveBackupPath(string $backup): string
    {
        $backup = str_replace(['..', '\\'], ['', '/'], $backup);
        $basename = basename($backup);

        if ($basename !== $backup) {
            throw new RuntimeException(__('scf.release.backup_path_unsafe'));
        }

        $candidates = [
            $this->backupDirectory().DIRECTORY_SEPARATOR.$basename,
            $backup,
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $real = realpath($candidate);
                $dir = realpath($this->backupDirectory());

                if ($real === false || $dir === false || ! str_starts_with($real, $dir)) {
                    throw new RuntimeException(__('scf.release.backup_path_unsafe'));
                }

                return $real;
            }
        }

        throw new RuntimeException(__('scf.release.backup_not_found'));
    }

    protected function verifySqliteBackup(string $path): bool
    {
        try {
            $pdo = new \PDO('sqlite:'.$path);

            $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' LIMIT 1");
            if ($statement === false) {
                return false;
            }

            return (bool) $statement->fetch();
        } catch (\Throwable) {
            return false;
        }
    }

    protected function verifySqlBackup(string $path): bool
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 4096) ?: '';
        fclose($handle);

        return str_contains($header, 'PostgreSQL') || str_contains($header, 'CREATE TABLE') || str_contains($header, 'SET ');
    }

    protected function restoreSqlite(string $backupPath): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($database === '' || $database === ':memory:') {
            throw new RuntimeException('Cannot restore to an in-memory SQLite database.');
        }

        if (! File::copy($backupPath, $database)) {
            throw new RuntimeException(__('scf.release.restore_failed'));
        }
    }

    protected function restorePostgres(string $backupPath): void
    {
        if (! $this->commandExists('psql')) {
            throw new RuntimeException(__('scf.release.restore_psql_missing'));
        }

        $connection = config("database.connections.{$this->connectionName()}");
        $host = $connection['host'] ?? '127.0.0.1';
        $port = (string) ($connection['port'] ?? 5432);
        $database = (string) ($connection['database'] ?? '');
        $username = (string) ($connection['username'] ?? '');

        $command = [
            'psql',
            '--host='.$host,
            '--port='.$port,
            '--username='.$username,
            '--dbname='.$database,
            '--file='.$backupPath,
            '--single-transaction',
            '--set=ON_ERROR_STOP=on',
        ];

        $env = [];
        if (! empty($connection['password'])) {
            $env['PGPASSWORD'] = (string) $connection['password'];
        }

        $result = Process::env($env)->run($command);

        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput()) ?: __('scf.release.restore_failed'));
        }
    }

    protected function commandExists(string $command): bool
    {
        $result = Process::run(['which', $command]);

        return $result->successful();
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
