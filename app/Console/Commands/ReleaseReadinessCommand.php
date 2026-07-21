<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RendersDeploymentChecks;
use App\Services\Deployment\DeploymentCheckService;
use Illuminate\Console\Command;

class ReleaseReadinessCommand extends Command
{
  use RendersDeploymentChecks;

  protected $signature = 'scf:release-readiness {--json : Output machine-readable JSON}';

  protected $description = 'Evaluate Version 1.0 production readiness';

  public function handle(DeploymentCheckService $checks): int
  {
    $results = $checks->readinessChecks();
    $summary = $checks->summarize($results);

    if ($this->option('json')) {
      $this->line(json_encode([
        'readiness' => $summary['status'],
        'summary' => $summary,
        'checks' => $results,
        'release' => \App\Support\Release\ReleaseMetadata::public(),
      ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

      return $summary['status'] === 'not_ready' ? self::FAILURE : self::SUCCESS;
    }

    $this->info(__('scf.release.readiness_heading'));

    $statusLabel = match ($summary['status']) {
      'ready' => '<info>'.__('scf.release.readiness_ready').'</info>',
      'ready_with_warnings' => '<comment>'.__('scf.release.readiness_warnings').'</comment>',
      default => '<error>'.__('scf.release.readiness_not_ready').'</error>',
    };

    $this->line(__('scf.release.readiness_status', ['status' => $statusLabel]));
    $exit = $this->renderChecks($results);

    if ($summary['warnings'] > 0) {
      $this->warn(__('scf.release.readiness_review_warnings'));
    }

    return $exit;
  }
}
