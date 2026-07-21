<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Console\Command;

trait RendersDeploymentChecks
{
  /**
   * @param  list<array{key: string, status: string, message: string, category: string}>  $checks
   */
  protected function renderChecks(array $checks, bool $asJson = false): int
  {
    $summary = app(\App\Services\Deployment\DeploymentCheckService::class)->summarize($checks);

    if ($asJson) {
      $this->line(json_encode([
        'summary' => $summary,
        'checks' => $checks,
      ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

      return $summary['failures'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    foreach ($checks as $check) {
      $prefix = match ($check['status']) {
        'pass' => '<info>[PASS]</info>',
        'warn' => '<comment>[WARN]</comment>',
        default => '<error>[FAIL]</error>',
      };
      $this->line("{$prefix} [{$check['category']}] {$check['message']}");
    }

    $this->newLine();
    $this->line(__('scf.release.summary_line', [
      'passes' => $summary['passes'],
      'warnings' => $summary['warnings'],
      'failures' => $summary['failures'],
    ]));

    if (app()->isProduction()) {
      $this->warn(__('scf.release.production_warning'));
    }

    return $summary['failures'] > 0 ? Command::FAILURE : Command::SUCCESS;
  }
}
