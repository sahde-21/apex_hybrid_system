<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ValidateEnvironmentCommand extends Command
{
    protected $signature = 'scf:validate-env';

    protected $description = 'Validate environment and production readiness settings';

    /**
     * @var list<array{level: string, message: string}>
     */
    protected array $results = [];

    public function handle(): int
    {
        $this->checkAppKey();
        $this->checkDebugMode();
        $this->checkAppUrl();
        $this->checkDatabase();
        $this->checkCache();
        $this->checkQueue();
        $this->checkSession();
        $this->checkMail();
        $this->checkFilesystem();
        $this->checkLogging();
        $this->checkBackupDirectory();

        $this->newLine();
        foreach ($this->results as $result) {
            $prefix = match ($result['level']) {
                'pass' => '<info>[PASS]</info>',
                'warn' => '<comment>[WARN]</comment>',
                default => '<error>[FAIL]</error>',
            };
            $this->line("{$prefix} {$result['message']}");
        }

        $failed = collect($this->results)->contains(fn ($r) => $r['level'] === 'fail');

        if ($failed) {
            $this->error(__('scf.performance.env_validation_failed'));

            return self::FAILURE;
        }

        $this->info(__('scf.performance.env_validation_passed'));

        return self::SUCCESS;
    }

    protected function record(string $level, string $message): void
    {
        $this->results[] = ['level' => $level, 'message' => $message];
    }

    protected function checkAppKey(): void
    {
        $this->record(
            config('app.key') ? 'pass' : 'fail',
            __('scf.performance.env_app_key'),
        );
    }

    protected function checkDebugMode(): void
    {
        if (app()->isProduction() && config('app.debug')) {
            $this->record('fail', __('scf.performance.env_debug_production'));

            return;
        }

        $this->record('pass', __('scf.performance.env_debug_ok'));
    }

    protected function checkAppUrl(): void
    {
        $url = (string) config('app.url');
        $level = $url !== '' && $url !== 'http://localhost' ? 'pass' : 'warn';
        $this->record($level, __('scf.performance.env_app_url'));
    }

    protected function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $this->record('pass', __('scf.performance.env_database_ok', [
                'driver' => config('database.default'),
            ]));
        } catch (\Throwable) {
            $this->record('fail', __('scf.performance.env_database_fail'));
        }
    }

    protected function checkCache(): void
    {
        $driver = (string) config('cache.default');
        $level = in_array($driver, ['file', 'database', 'array'], true) ? 'pass' : 'warn';
        $this->record($level, __('scf.performance.env_cache_driver', ['driver' => $driver]));
    }

    protected function checkQueue(): void
    {
        $driver = (string) config('queue.default');
        $level = in_array($driver, ['database', 'sync'], true) ? 'pass' : 'warn';
        $this->record($level, __('scf.performance.env_queue_driver', ['driver' => $driver]));
    }

    protected function checkSession(): void
    {
        $driver = (string) config('session.driver');
        $this->record('pass', __('scf.performance.env_session_driver', ['driver' => $driver]));
    }

    protected function checkMail(): void
    {
        $mailer = (string) config('mail.default');
        $level = in_array($mailer, ['log', 'array', 'smtp'], true) ? 'pass' : 'warn';
        $this->record($level, __('scf.performance.env_mail_driver', ['mailer' => $mailer]));
    }

    protected function checkFilesystem(): void
    {
        $disk = (string) config('filesystems.default');
        $this->record('pass', __('scf.performance.env_filesystem_disk', ['disk' => $disk]));
    }

    protected function checkLogging(): void
    {
        $channel = (string) config('logging.default');
        $this->record('pass', __('scf.performance.env_log_channel', ['channel' => $channel]));
    }

    protected function checkBackupDirectory(): void
    {
        $directory = database_path('backups');
        File::ensureDirectoryExists($directory);
        $this->record(
            is_writable($directory) ? 'pass' : 'warn',
            __('scf.performance.env_backup_directory'),
        );
    }
}
