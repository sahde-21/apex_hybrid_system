<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RendersDeploymentChecks;
use App\Services\Deployment\DeploymentCheckService;
use Illuminate\Console\Command;

class DeployVerifyCommand extends Command
{
  use RendersDeploymentChecks;

  protected $signature = 'scf:deploy-verify {--json : Output machine-readable JSON}';

  protected $description = 'Run non-destructive post-deployment verification checks';

  public function handle(DeploymentCheckService $checks): int
  {
    $results = $checks->postDeployChecks();

    $this->info(__('scf.release.deploy_verify_heading'));
    $exit = $this->renderChecks($results, (bool) $this->option('json'));

    if ($exit === self::SUCCESS) {
      $this->info(__('scf.release.deploy_verify_passed'));
    } else {
      $this->error(__('scf.release.deploy_verify_failed'));
    }

    return $exit;
  }
}
