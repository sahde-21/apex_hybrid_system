<?php

namespace App\Console\Commands;

use App\Support\Release\ReleaseMetadata;
use Illuminate\Console\Command;

class ReleaseInfoCommand extends Command
{
  protected $signature = 'scf:release-info {--json : Output machine-readable JSON}';

  protected $description = 'Display application release and runtime metadata';

  public function handle(): int
  {
    $data = ReleaseMetadata::runtime();

    if ($this->option('json')) {
      $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

      return self::SUCCESS;
    }

    $this->info(__('scf.release.info_heading'));
    $this->table(
      [__('scf.release.info_key'), __('scf.release.info_value')],
      collect($data)->map(fn ($value, $key) => [$key, is_bool($value) ? ($value ? 'true' : 'false') : (string) ($value ?? '—')])->values()->all(),
    );

    if (app()->isProduction() && config('app.debug')) {
      $this->error(__('scf.release.production_debug_warning'));
    }

    if (app()->isDownForMaintenance()) {
      $this->warn(__('scf.release.maintenance_active'));
    }

    return self::SUCCESS;
  }
}
