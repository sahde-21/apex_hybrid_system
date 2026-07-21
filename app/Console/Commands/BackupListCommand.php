<?php

namespace App\Console\Commands;

use App\Support\Database\DatabaseBackupService;
use Illuminate\Console\Command;

class BackupListCommand extends Command
{
  protected $signature = 'scf:backup:list {--json : Output machine-readable JSON}';

  protected $description = 'List local database backups';

  public function handle(DatabaseBackupService $backups): int
  {
    $items = $backups->listBackups();

    if ($this->option('json')) {
      $this->line(json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

      return self::SUCCESS;
    }

    $this->info(__('scf.release.backup_list_heading'));

    if ($items === []) {
      $this->warn(__('scf.release.backup_list_empty'));

      return self::SUCCESS;
    }

    $this->table(
      [__('scf.release.backup_filename'), __('scf.release.backup_size'), __('scf.release.backup_modified')],
      collect($items)->map(fn ($item) => [
        $item['filename'],
        number_format($item['size'] / 1024, 1).' KB',
        $item['modified_at'],
      ])->all(),
    );

    return self::SUCCESS;
  }
}
