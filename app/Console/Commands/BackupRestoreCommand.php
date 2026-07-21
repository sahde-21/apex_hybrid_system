<?php

namespace App\Console\Commands;

use App\Support\Database\DatabaseBackupService;
use Illuminate\Console\Command;

class BackupRestoreCommand extends Command
{
  protected $signature = 'scf:backup:restore
                            {backup : Backup filename in the backup directory}
                            {--execute : Perform the restore (requires maintenance mode and confirmation)}
                            {--force : Skip confirmation prompt}';

  protected $description = 'Restore a local database backup (dry-run by default)';

  public function handle(DatabaseBackupService $backups): int
  {
    $execute = (bool) $this->option('execute');

    try {
      if ($execute) {
        $this->warn(__('scf.release.restore_warning'));
        $this->warn(__('scf.release.restore_data_loss'));

        if (! $this->option('force') && ! $this->confirm(__('scf.release.restore_confirm'))) {
          $this->warn(__('scf.release.restore_cancelled'));

          return self::FAILURE;
        }
      }

      $result = $backups->restore($this->argument('backup'), $execute);
    } catch (\Throwable $exception) {
      $this->error($exception->getMessage());

      return self::FAILURE;
    }

    if ($result['dry_run']) {
      $this->info(__('scf.release.restore_dry_run_heading'));
      $this->line($result['message']);
      $this->comment(__('scf.release.restore_dry_run_hint'));

      return self::SUCCESS;
    }

    $this->info($result['message']);

    if (isset($result['safety_backup'])) {
      $this->line(__('scf.release.restore_safety_backup', ['path' => basename($result['safety_backup'])]));
    }

    return self::SUCCESS;
  }
}
