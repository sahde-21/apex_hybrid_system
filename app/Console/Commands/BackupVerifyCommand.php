<?php

namespace App\Console\Commands;

use App\Support\Database\DatabaseBackupService;
use Illuminate\Console\Command;

class BackupVerifyCommand extends Command
{
  protected $signature = 'scf:backup:verify {backup : Backup filename or path} {--json : Output machine-readable JSON}';

  protected $description = 'Verify a local database backup file';

  public function handle(DatabaseBackupService $backups): int
  {
    try {
      $result = $backups->verifyBackup($this->argument('backup'));
    } catch (\Throwable $exception) {
      $this->error($exception->getMessage());

      return self::FAILURE;
    }

    if ($this->option('json')) {
      $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

      return $result['valid'] ? self::SUCCESS : self::FAILURE;
    }

    $this->info(__('scf.release.backup_verify_heading', ['file' => basename($this->argument('backup'))]));
    $this->line($result['message']);

    if ($result['valid']) {
      $this->line(__('scf.release.backup_verify_driver', ['driver' => $result['driver']]));
      $this->line(__('scf.release.backup_verify_size', ['size' => number_format($result['size'] / 1024, 1)]));

      return self::SUCCESS;
    }

    return self::FAILURE;
  }
}
