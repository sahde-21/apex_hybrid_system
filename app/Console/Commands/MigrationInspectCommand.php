<?php

namespace App\Console\Commands;

use App\Services\Deployment\MigrationInspectionService;
use Illuminate\Console\Command;

class MigrationInspectCommand extends Command
{
  protected $signature = 'scf:migrations:inspect {--json : Output machine-readable JSON}';

  protected $description = 'Inspect pending migrations and flag potentially risky changes';

  public function handle(MigrationInspectionService $inspector): int
  {
    $report = $inspector->inspect();

    if ($this->option('json')) {
      $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

      return self::SUCCESS;
    }

    $this->info(__('scf.release.migrations_inspect_heading'));
    $this->line(__('scf.release.migrations_pending_count', ['count' => count($report['pending'])]));
    $this->line(__('scf.release.migrations_applied_count', ['count' => count($report['applied'])]));

    if ($report['pending'] !== []) {
      $this->newLine();
      $this->comment(__('scf.release.migrations_pending_list'));
      foreach ($report['pending'] as $migration) {
        $this->line("  - {$migration}");
      }
    }

    if ($report['risky'] !== []) {
      $this->newLine();
      $this->warn(__('scf.release.migrations_risky_heading'));
      foreach ($report['risky'] as $item) {
        $this->line("  - {$item['file']}: ".implode(', ', $item['keywords']));
      }
      $this->warn(__('scf.release.migrations_risky_disclaimer'));
    } else {
      $this->info(__('scf.release.migrations_no_risky_keywords'));
    }

    return self::SUCCESS;
  }
}
